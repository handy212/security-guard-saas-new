<?php

namespace App\Jobs;

use App\Models\OfflineSyncBatch;
use App\Services\OfflineSyncService;
use App\Services\TenantScopeService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessOfflineSyncBatch implements ShouldQueue
{
    use Queueable;

    public function __construct(public OfflineSyncBatch $batch)
    {
    }

    public function handle(OfflineSyncService $service, TenantScopeService $tenantScope): void
    {
        $tenantScope->runForTenant($this->batch->tenant_id, function () use ($service) {
            $service->process($this->batch->fresh());
        });
    }
}
