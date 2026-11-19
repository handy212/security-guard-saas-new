<?php

namespace App\Services;

use App\Exceptions\PatrolException;
use App\Models\Guard;
use App\Models\GuardLocation;
use App\Models\JwmCheckpointMapping;
use App\Models\JwmDevice;
use App\Models\JwmDeviceAssignment;
use App\Models\JwmDeviceEvent;
use App\Models\JwmGuardMapping;
use App\Models\PatrolSession;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JwmDeviceIngestionService
{
    public function __construct(private PatrolService $patrols) {}

    public function ingest(int $tenantId, int $deviceId, int $tokenId, array $data): array
    {
        $occurredAt = CarbonImmutable::parse($data['occurred_at'])->utc();
        $normalized = [
            'type' => $data['type'],
            'occurred_at' => $occurredAt->toIso8601String(),
            'latitude' => isset($data['latitude']) ? (float) $data['latitude'] : null,
            'longitude' => isset($data['longitude']) ? (float) $data['longitude'] : null,
            'patrol_session_id' => isset($data['patrol_session_id']) ? (int) $data['patrol_session_id'] : null,
            'checkpoint_code' => $data['checkpoint_code'] ?? null,
        ];
        // Keep legacy hashes unchanged when the new optional identifiers are absent.
        foreach (['guard_external_id', 'checkpoint_external_id'] as $field) {
            if (isset($data[$field])) {
                $normalized[$field] = $data[$field];
            }
        }
        $hash = hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($tenantId, $deviceId, $tokenId, $data, $occurredAt, $normalized, $hash) {
            // Serialize registration changes, handovers and event retries on the same device.
            $device = JwmDevice::where('tenant_id', $tenantId)->lockForUpdate()->findOrFail($deviceId);
            abort_unless($device->is_active && (int) $device->bridge_token_id === $tokenId, 403, 'Device is disabled or not authorized for this bridge.');

            $existing = JwmDeviceEvent::where('tenant_id', $tenantId)
                ->where('jwm_device_id', $deviceId)->where('event_id', $data['event_id'])->first();
            if ($existing) {
                abort_unless(hash_equals($existing->payload_hash, $hash), 409, 'Event ID was already used with different content.');

                return ['event' => $existing, 'replayed' => true];
            }

            if ($device->require_device_identity && $data['type'] === 'checkpoint') {
                foreach (['guard_external_id', 'checkpoint_external_id'] as $field) {
                    if (! isset($data[$field])) {
                        throw ValidationException::withMessages([$field => 'This device requires mapped guard and checkpoint identifiers.']);
                    }
                }
            }

            $assignments = JwmDeviceAssignment::where('tenant_id', $tenantId)
                ->where('jwm_device_id', $deviceId)->where('starts_at', '<=', $occurredAt)
                ->where('ends_at', '>', $occurredAt)->limit(2)->get();
            if ($assignments->count() !== 1) {
                throw ValidationException::withMessages(['occurred_at' => 'The event must match exactly one device guard-use interval.']);
            }
            $guardId = $assignments->first()->guard_id;
            if (isset($data['guard_external_id'])) {
                $mappedGuardId = JwmGuardMapping::where('tenant_id', $tenantId)
                    ->where('jwm_device_id', $deviceId)->where('external_id_hash', hash('sha256', $data['guard_external_id']))
                    ->value('guard_id');
                if (! $mappedGuardId || (int) $mappedGuardId !== (int) $guardId
                    || ! Guard::where('tenant_id', $tenantId)->whereKey($mappedGuardId)->exists()) {
                    throw ValidationException::withMessages(['guard_external_id' => 'The external guard ID must map to this device interval\'s guard in this tenant.']);
                }
            }
            $event = JwmDeviceEvent::create([
                'tenant_id' => $tenantId, 'jwm_device_id' => $deviceId,
                'event_id' => $data['event_id'], 'payload_hash' => $hash,
                'type' => $data['type'], 'occurred_at' => $occurredAt, 'guard_id' => $guardId,
                'guard_external_id' => $data['guard_external_id'] ?? null,
                'checkpoint_external_id' => $data['checkpoint_external_id'] ?? null,
            ]);

            if ($data['type'] === 'gps') {
                $location = GuardLocation::create([
                    'tenant_id' => $tenantId, 'guard_id' => $guardId,
                    'latitude' => $normalized['latitude'], 'longitude' => $normalized['longitude'],
                    'source' => 'jwm', 'recorded_at' => $occurredAt,
                ]);
                $event->update(['guard_location_id' => $location->id]);
            } else {
                $session = PatrolSession::where('tenant_id', $tenantId)
                    ->where('guard_id', $guardId)->lockForUpdate()->find($normalized['patrol_session_id']);
                if (! $session || $session->status !== 'in_progress' || $session->completed_at
                    || ! $session->started_at || $occurredAt->lessThan($session->started_at)) {
                    throw ValidationException::withMessages(['patrol_session_id' => 'The event must belong to this guard and an in-progress patrol that had already started.']);
                }
                $checkpoints = $session->route->checkpoints()->where('tenant_id', $tenantId);
                $checkpointField = isset($data['checkpoint_external_id']) ? 'checkpoint_external_id' : 'checkpoint_code';
                if (isset($data['checkpoint_external_id'])) {
                    $checkpointId = JwmCheckpointMapping::where('tenant_id', $tenantId)
                        ->where('jwm_device_id', $deviceId)->where('external_id_hash', hash('sha256', $data['checkpoint_external_id']))
                        ->value('patrol_checkpoint_id');
                    $checkpoints->whereKey($checkpointId);
                } else {
                    $checkpoints->where('code', $normalized['checkpoint_code']);
                }
                $checkpoint = $checkpoints->first();
                if (! $checkpoint) {
                    throw ValidationException::withMessages([$checkpointField => 'Checkpoint must resolve to an existing checkpoint in this tenant and patrol route.']);
                }
                $lastScanAt = $session->scans()->max('scanned_at');
                if ($lastScanAt && $occurredAt->lessThan(CarbonImmutable::parse($lastScanAt))) {
                    throw ValidationException::withMessages(['occurred_at' => 'Out-of-order checkpoint uploads require operator review.']);
                }

                try {
                    $scan = $this->patrols->scanCheckpoint([
                        'patrol_session_id' => $session->id,
                        'checkpoint_code' => $checkpoint->code,
                        'latitude' => $normalized['latitude'], 'longitude' => $normalized['longitude'],
                    ], $occurredAt);
                } catch (PatrolException $exception) {
                    throw ValidationException::withMessages([$checkpointField => $exception->getMessage()]);
                }
                $event->update(['checkpoint_scan_id' => $scan->id]);
            }

            return ['event' => $event, 'replayed' => false];
        });
    }
}
