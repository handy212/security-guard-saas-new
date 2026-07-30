<?php

namespace App\Jobs;

use App\Models\Guard;
use App\Services\GuardIdCardPdfService;
use App\Services\TenantScopeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GenerateGuardIdCardBulkZipJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        public int $tenantId,
        public string $resultToken,
        public string $format = 'pdf',
    ) {
        $this->onQueue('heavy');
    }

    public static function cacheKey(string $token): string
    {
        return 'id-card-bulk:'.$token;
    }

    public static function statusKey(string $token): string
    {
        return 'id-card-bulk-status:'.$token;
    }

    public function handle(
        GuardIdCardPdfService $pdf,
        TenantScopeService $tenantScope,
    ): void {
        $format = in_array($this->format, ['pdf', 'png'], true) ? $this->format : 'pdf';

        $tenantScope->runForTenant($this->tenantId, function () use ($pdf, $format) {
            $guards = Guard::query()
                ->where('tenant_id', $this->tenantId)
                ->where('verification_status', 'verified')
                ->with(['tenant', 'branch', 'verificationTokens'])
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get()
                ->filter(fn (Guard $guard) => $guard->activeVerificationToken() !== null)
                ->values();

            $binary = $pdf->generateBulkZip($guards, $format);

            Cache::put(self::cacheKey($this->resultToken), $binary, now()->addMinutes(15));
            Cache::put(self::statusKey($this->resultToken), 'ready', now()->addMinutes(15));
        });
    }

    public function failed(?Throwable $e): void
    {
        $message = $e?->getMessage() ?: 'Bulk ID card export failed.';

        Cache::put(
            self::statusKey($this->resultToken),
            'failed:'.$message,
            now()->addMinutes(15),
        );
    }
}
