<?php

namespace App\Services;

use App\Models\OfflineSyncBatch;
use App\Models\PatrolPlaybackPoint;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OfflineSyncService
{
    public function __construct(
        private PatrolService $patrol,
        private AttendanceService $attendance,
        private DispatchService $dispatch,
        private GuardLocationService $locations,
    ) {}

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
        $user = $batch->user_id ? User::with('guardProfile')->find($batch->user_id) : null;
        $processed = [];
        $errors = [];

        foreach ($batch->payload as $item) {
            $type = $item['type'] ?? null;

            try {
                match ($type) {
                    'playback_point' => $this->processPlaybackPoint($batch, $item),
                    'checkpoint_scan' => $this->patrol->scanCheckpoint([
                        'patrol_session_id' => $item['patrol_session_id'],
                        'checkpoint_code' => $item['checkpoint_code'],
                        'latitude' => $item['latitude'],
                        'longitude' => $item['longitude'],
                        'notes' => $item['notes'] ?? null,
                    ]),
                    'clock_in' => $this->attendance->clockIn(
                        (int) $item['shift_assignment_id'],
                        (float) $item['latitude'],
                        (float) $item['longitude'],
                        (bool) ($item['enforce_geofence'] ?? false),
                    ),
                    'clock_out' => $this->attendance->clockOut(
                        (int) $item['attendance_log_id'],
                        (float) $item['latitude'],
                        (float) $item['longitude'],
                    ),
                    'sos' => $user
                        ? $this->dispatch->raiseSos($user, [
                            'site_id' => $item['site_id'] ?? null,
                            'latitude' => $item['latitude'],
                            'longitude' => $item['longitude'],
                            'message' => $item['message'] ?? 'Offline SOS replay',
                        ])
                        : throw new RuntimeException('User required for SOS sync.'),
                    'location' => $user
                        ? $this->locations->record($user, (float) $item['latitude'], (float) $item['longitude'], $item['accuracy_meters'] ?? null)
                        : throw new RuntimeException('User required for location sync.'),
                    default => Log::info('Unhandled offline sync item', ['type' => $type, 'batch_id' => $batch->id]),
                };
                $processed[] = $type;
            } catch (\Throwable $e) {
                $errors[] = ['type' => $type, 'error' => $e->getMessage()];
                Log::warning('Offline sync item failed', ['type' => $type, 'batch_id' => $batch->id, 'error' => $e->getMessage()]);
            }
        }

        $status = $errors ? (empty($processed) ? 'failed' : 'partial') : 'processed';

        return $this->markProcessed($batch, ['processed' => $processed, 'errors' => $errors], $status);
    }

    private function processPlaybackPoint(OfflineSyncBatch $batch, array $item): void
    {
        PatrolPlaybackPoint::create([
            'tenant_id' => $batch->tenant_id,
            'patrol_session_id' => $item['patrol_session_id'],
            'latitude' => $item['latitude'],
            'longitude' => $item['longitude'],
            'recorded_at' => $item['recorded_at'] ?? now(),
        ]);
    }

    public function markProcessed(OfflineSyncBatch $batch, array $result, string $status = 'processed'): OfflineSyncBatch
    {
        $batch->update(['status' => $status, 'result' => $result, 'processed_at' => now()]);

        return $batch;
    }
}
