<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateGuardIdCardBulkZipJob;
use App\Models\Guard;
use App\Services\GuardIdCardPdfService;
use App\Services\GuardIdCardPresenter;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GuardIdCardBulkDownloadController extends Controller
{
    private const HEAVY_WAIT_SECONDS = 120;

    private const HEAVY_POLL_MICROSECONDS = 500_000;

    public function __invoke(
        Request $request,
        GuardIdCardPdfService $pdf,
        GuardIdCardPresenter $presenter,
    ): Response {
        abort_unless(auth()->user()->can('guards.manage'), 403);

        $format = strtolower((string) $request->query('format', 'pdf'));
        abort_unless(in_array($format, ['pdf', 'png'], true), 422, 'Format must be pdf or png.');

        $tenantId = (int) TenantContext::id();

        $guards = Guard::query()
            ->where('tenant_id', $tenantId)
            ->where('verification_status', 'verified')
            ->with(['tenant', 'branch', 'verificationTokens'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->filter(fn (Guard $guard) => $guard->activeVerificationToken() !== null)
            ->values();

        abort_if($guards->isEmpty(), 404, 'No verified guards with active QR tokens to download.');

        $filename = 'verified-id-cards-'.now()->format('Y-m-d').'.zip';

        $sampleBrand = $presenter->branding($guards->first()->tenant, $guards->first()->branch);
        $needsHeavy = $format === 'png'
            || $pdf->requiresHeavyWorker(['brand' => $sampleBrand], 'pdf')
            || $guards->count() > 3;

        if ($needsHeavy) {
            return $this->downloadViaHeavyWorker($tenantId, $format, $filename);
        }

        $binary = $pdf->generateBulkZip($guards, $format);

        return response($binary, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function downloadViaHeavyWorker(int $tenantId, string $format, string $filename): Response
    {
        $token = (string) Str::uuid();

        Cache::put(GenerateGuardIdCardBulkZipJob::statusKey($token), 'pending', now()->addMinutes(15));

        GenerateGuardIdCardBulkZipJob::dispatch($tenantId, $token, $format);

        $deadline = microtime(true) + self::HEAVY_WAIT_SECONDS;

        while (microtime(true) < $deadline) {
            $status = Cache::get(GenerateGuardIdCardBulkZipJob::statusKey($token));

            if ($status === 'ready') {
                $binary = Cache::pull(GenerateGuardIdCardBulkZipJob::cacheKey($token));
                Cache::forget(GenerateGuardIdCardBulkZipJob::statusKey($token));

                abort_unless(is_string($binary) && $binary !== '', 503, 'Bulk ID card export was empty after generation.');

                return response($binary, 200, [
                    'Content-Type' => 'application/zip',
                    'Content-Disposition' => 'attachment; filename="'.$filename.'"',
                ]);
            }

            if (is_string($status) && str_starts_with($status, 'failed:')) {
                Cache::forget(GenerateGuardIdCardBulkZipJob::statusKey($token));
                abort(503, 'Bulk ID card export failed: '.substr($status, 7));
            }

            usleep(self::HEAVY_POLL_MICROSECONDS);
        }

        abort(503, 'Bulk ID card export timed out. Ensure the queue-heavy worker is running.');
    }
}
