<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateGuardIdCardPdfJob;
use App\Models\Guard;
use App\Services\GuardIdCardPdfService;
use App\Services\GuardIdCardPresenter;
use App\Services\GuardIdCardRenderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GuardIdCardController extends Controller
{
    private const HEAVY_WAIT_SECONDS = 30;

    private const HEAVY_POLL_MICROSECONDS = 250_000;

    public function __invoke(
        Request $request,
        Guard $guard,
        GuardIdCardRenderService $renderer,
        GuardIdCardPdfService $pdf,
        GuardIdCardPresenter $presenter,
    ): Response {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        abort_unless((int) $guard->tenant_id === (int) auth()->user()->tenant_id, 404);
        abort_unless(in_array($guard->verification_status, ['verified', 'suspended'], true), 403, 'Guard must be verified before downloading an ID card.');

        $format = strtolower((string) $request->query('format', 'pdf'));
        abort_unless(in_array($format, ['pdf', 'png'], true), 422, 'Format must be pdf or png.');

        $basename = $pdf->safeBasename($guard);

        $guard->loadMissing(['tenant', 'branch']);
        $brand = $presenter->branding($guard->tenant, $guard->branch);

        if ($pdf->requiresHeavyWorker(['brand' => $brand], $format)) {
            return $this->downloadViaHeavyWorker($guard, $basename, $format);
        }

        $built = $renderer->forGuard($guard);

        try {
            if ($format === 'png') {
                return $pdf->downloadPngZipResponse($built['viewData'], $basename);
            }

            return $pdf->downloadResponse($built['viewData'], $basename.'.pdf');
        } finally {
            $renderer->cleanup($built['tempFiles']);
        }
    }

    private function downloadViaHeavyWorker(Guard $guard, string $basename, string $format): Response
    {
        $token = (string) Str::uuid();

        Cache::put(GenerateGuardIdCardPdfJob::statusKey($token), 'pending', now()->addMinutes(5));

        GenerateGuardIdCardPdfJob::dispatch($guard->id, $token, $format);

        $deadline = microtime(true) + self::HEAVY_WAIT_SECONDS;

        while (microtime(true) < $deadline) {
            $status = Cache::get(GenerateGuardIdCardPdfJob::statusKey($token));

            if ($status === 'ready') {
                $binary = Cache::pull(GenerateGuardIdCardPdfJob::cacheKey($token));
                Cache::forget(GenerateGuardIdCardPdfJob::statusKey($token));

                abort_unless(is_string($binary) && $binary !== '', 503, 'ID card export was empty after generation.');

                if ($format === 'png') {
                    return response($binary, 200, [
                        'Content-Type' => 'application/zip',
                        'Content-Disposition' => 'attachment; filename="'.$basename.'.zip"',
                    ]);
                }

                return response($binary, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="'.$basename.'.pdf"',
                ]);
            }

            if (is_string($status) && str_starts_with($status, 'failed:')) {
                Cache::forget(GenerateGuardIdCardPdfJob::statusKey($token));
                abort(503, 'ID card export failed: '.substr($status, 7));
            }

            usleep(self::HEAVY_POLL_MICROSECONDS);
        }

        abort(503, 'ID card export timed out. Ensure the queue-heavy worker is running.');
    }
}
