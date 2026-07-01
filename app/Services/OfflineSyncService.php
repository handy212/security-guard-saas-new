<?php

namespace App\Services;

use App\Models\AttendanceLog;
use App\Models\OfflineSyncBatch;
use App\Models\PatrolPlaybackPoint;
use App\Models\PatrolSession;
use App\Models\ShiftAssignment;
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

        $guardId = $this->guardIdForBatch($batch, $user);

        foreach ($batch->payload as $item) {
            $type = $item['type'] ?? null;

            try {
                match ($type) {
                    'playback_point' => $this->processPlaybackPoint($batch, $item, $guardId),
                    'checkpoint_scan' => $this->processCheckpointScan($batch, $item, $guardId),
                    'clock_in' => $this->processClockIn($batch, $item, $guardId),
                    'clock_out' => $this->processClockOut($batch, $item, $guardId),
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

    private function processPlaybackPoint(OfflineSyncBatch $batch, array $item, ?int $guardId): void
    {
        $this->ownedPatrolSession($batch->tenant_id, (int) $item['patrol_session_id'], $guardId);

        PatrolPlaybackPoint::create([
            'tenant_id' => $batch->tenant_id,
            'patrol_session_id' => $item['patrol_session_id'],
            'latitude' => $item['latitude'],
            'longitude' => $item['longitude'],
            'recorded_at' => $item['recorded_at'] ?? now(),
        ]);
    }

    private function processCheckpointScan(OfflineSyncBatch $batch, array $item, ?int $guardId): void
    {
        $session = $this->ownedPatrolSession($batch->tenant_id, (int) $item['patrol_session_id'], $guardId);

        $this->patrol->scanCheckpoint([
            'patrol_session_id' => $session->id,
            'checkpoint_code' => $item['checkpoint_code'],
            'latitude' => $item['latitude'],
            'longitude' => $item['longitude'],
            'notes' => $item['notes'] ?? null,
        ]);
    }

    private function processClockIn(OfflineSyncBatch $batch, array $item, ?int $guardId): void
    {
        $assignment = $this->ownedAssignment($batch->tenant_id, (int) $item['shift_assignment_id'], $guardId);

        $this->attendance->clockIn(
            $assignment->id,
            (float) $item['latitude'],
            (float) $item['longitude'],
            (bool) ($item['enforce_geofence'] ?? false),
        );
    }

    private function processClockOut(OfflineSyncBatch $batch, array $item, ?int $guardId): void
    {
        $log = $this->ownedAttendanceLog($batch->tenant_id, (int) $item['attendance_log_id'], $guardId);

        $this->attendance->clockOut(
            $log->id,
            (float) $item['latitude'],
            (float) $item['longitude'],
        );
    }

    private function guardIdForBatch(OfflineSyncBatch $batch, ?User $user): ?int
    {
        return $user?->guardProfile?->id;
    }

    private function ownedAssignment(int $tenantId, int $assignmentId, ?int $guardId): ShiftAssignment
    {
        if (! $guardId) {
            throw new RuntimeException('Guard profile is required for offline sync.');
        }

        return ShiftAssignment::query()
            ->where('id', $assignmentId)
            ->where('guard_id', $guardId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();
    }

    private function ownedAttendanceLog(int $tenantId, int $logId, ?int $guardId): AttendanceLog
    {
        if (! $guardId) {
            throw new RuntimeException('Guard profile is required for offline sync.');
        }

        return AttendanceLog::query()
            ->where('id', $logId)
            ->where('guard_id', $guardId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();
    }

    private function ownedPatrolSession(int $tenantId, int $sessionId, ?int $guardId): PatrolSession
    {
        if (! $guardId) {
            throw new RuntimeException('Guard profile is required for offline sync.');
        }

        return PatrolSession::query()
            ->where('id', $sessionId)
            ->where('guard_id', $guardId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();
    }

    public function markProcessed(OfflineSyncBatch $batch, array $result, string $status = 'processed'): OfflineSyncBatch
    {
        $batch->update(['status' => $status, 'result' => $result, 'processed_at' => now()]);

        return $batch;
    }
}
