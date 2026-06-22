<?php

namespace App\Services;

use App\Models\OfflineSyncBatch;
use App\Models\PatrolPlaybackPoint;
use Illuminate\Support\Facades\Log;

class OfflineSyncService
{
    public function queue(array $data): OfflineSyncBatch
    {
        return OfflineSyncBatch::create([
            'tenant_id' => $data['tenant_id'],
            'user_id' => $data['user_id'] ?? null,
            'device_uuid' => $data['device_uuid'] ?? null,
            'payload' => $data['payload'],
            'status' => 'pending',
        ]);
    }

    public function process(OfflineSyncBatch $batch): OfflineSyncBatch
    {
        $processed = [];

        foreach ($batch->payload as $item) {
            $type = $item['type'] ?? null;

            if ($type === 'playback_point') {
                PatrolPlaybackPoint::create([
                    'tenant_id' => $batch->tenant_id,
                    'patrol_session_id' => $item['patrol_session_id'],
                    'latitude' => $item['latitude'],
                    'longitude' => $item['longitude'],
                    'recorded_at' => $item['recorded_at'] ?? now(),
                ]);
                $processed[] = $type;
            } else {
                Log::info('Unhandled offline sync item', ['type' => $type, 'batch_id' => $batch->id]);
            }
        }

        return $this->markProcessed($batch, ['processed' => $processed]);
    }

    public function markProcessed(OfflineSyncBatch $batch, array $result): OfflineSyncBatch
    {
        $batch->update(['status' => 'processed', 'result' => $result, 'processed_at' => now()]);

        return $batch;
    }
}
