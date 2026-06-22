<?php

namespace App\Jobs;

use App\Models\OfflineSyncBatch;
use App\Services\OfflineSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessOfflineSyncBatch implements ShouldQueue
{
    use Queueable;

    public function __construct(public OfflineSyncBatch $batch)
    {
    }

    public function handle(OfflineSyncService $service): void
    {
        $service->process($this->batch);
    }
}
