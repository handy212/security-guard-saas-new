<?php

namespace App\Jobs;

use App\Models\Guard;
use App\Services\GuardIdCardPdfService;
use App\Services\GuardIdCardRenderService;
use App\Services\TenantScopeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Throwable;

class GenerateGuardIdCardPdfJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public int $tries = 2;

    public function __construct(
        public int $guardId,
        public string $resultToken,
        public string $format = 'pdf',
    ) {
        $this->onQueue('heavy');
    }

    public static function cacheKey(string $token): string
    {
        return 'id-card-export:'.$token;
    }

    public static function statusKey(string $token): string
    {
        return 'id-card-export-status:'.$token;
    }

    public function handle(
        GuardIdCardRenderService $renderer,
        GuardIdCardPdfService $pdf,
        TenantScopeService $tenantScope,
    ): void {
        $guard = Guard::query()->findOrFail($this->guardId);
        $format = in_array($this->format, ['pdf', 'png'], true) ? $this->format : 'pdf';

        $tenantScope->runForTenant((int) $guard->tenant_id, function () use ($guard, $renderer, $pdf, $format) {
            $built = $renderer->forGuard($guard);

            try {
                $basename = $pdf->safeBasename($guard);
                $binary = $format === 'png'
                    ? $pdf->generatePngZip($built['viewData'], $basename)
                    : $pdf->generate($built['viewData']);

                Cache::put(self::cacheKey($this->resultToken), $binary, now()->addMinutes(5));
                Cache::put(self::statusKey($this->resultToken), 'ready', now()->addMinutes(5));
            } finally {
                $renderer->cleanup($built['tempFiles']);
            }
        });
    }

    public function failed(?Throwable $e): void
    {
        $message = $e?->getMessage() ?: 'ID card export failed.';

        Cache::put(
            self::statusKey($this->resultToken),
            'failed:'.$message,
            now()->addMinutes(5),
        );
    }
}
