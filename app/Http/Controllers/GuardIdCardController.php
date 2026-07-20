<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateGuardIdCardPdfJob;
use App\Models\Guard;
use App\Services\GuardIdCardPdfService;
use App\Services\GuardIdCardPresenter;
use App\Services\GuardIdCardRenderService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GuardIdCardController extends Controller
{
    private const HEAVY_WAIT_SECONDS = 30;

    private const HEAVY_POLL_MICROSECONDS = 250_000;

    public function __invoke(
        Guard $guard,
        GuardIdCardRenderService $renderer,
        GuardIdCardPdfService $pdf,
        GuardIdCardPresenter $presenter,
    ): Response {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        abort_unless((int) $guard->tenant_id === (int) auth()->user()->tenant_id, 404);
        abort_unless(in_array($guard->verification_status, ['verified', 'suspended'], true), 403, 'Guard must be verified before downloading an ID card.');

        $filename = 'guard-id-'.preg_replace('/[^a-zA-Z0-9._-]+/', '-', $guard->employee_number ?: (string) $guard->id).'.pdf';

        $guard->loadMissing(['tenant', 'branch']);
        $brand = $presenter->branding($guard->tenant, $guard->branch);

        if ($pdf->requiresHeavyWorker(['brand' => $brand])) {
            return $this->downloadViaHeavyWorker($guard, $filename);
        }

        $built = $renderer->forGuard($guard);

        try {
            return $pdf->downloadResponse($built['viewData'], $filename);
        } finally {
            $renderer->cleanup($built['tempFiles']);
        }
    }

    private function downloadViaHeavyWorker(Guard $guard, string $filename): Response
    {
        $token = (string) Str::uuid();

        Cache::put(GenerateGuardIdCardPdfJob::statusKey($token), 'pending', now()->addMinutes(5));

        GenerateGuardIdCardPdfJob::dispatch($guard->id, $token);

        $deadline = microtime(true) + self::HEAVY_WAIT_SECONDS;

        while (microtime(true) < $deadline) {
            $status = Cache::get(GenerateGuardIdCardPdfJob::statusKey($token));

            if ($status === 'ready') {
                $binary = Cache::pull(GenerateGuardIdCardPdfJob::cacheKey($token));
                Cache::forget(GenerateGuardIdCardPdfJob::statusKey($token));

                abort_unless(is_string($binary) && $binary !== '', 503, 'ID card PDF was empty after generation.');

                return response($binary, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                ]);
            }

            if (is_string($status) && str_starts_with($status, 'failed:')) {
                Cache::forget(GenerateGuardIdCardPdfJob::statusKey($token));
                abort(503, 'ID card PDF generation failed: '.substr($status, 7));
            }

            usleep(self::HEAVY_POLL_MICROSECONDS);
        }

        abort(503, 'ID card PDF generation timed out. Ensure the queue-heavy worker is running.');
    }
}
