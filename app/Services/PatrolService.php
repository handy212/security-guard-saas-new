<?php

namespace App\Services;

use App\Models\{CheckpointScan, PatrolCheckpoint, PatrolSession};
use RuntimeException;

class PatrolService
{
    public function startSession(array $data): PatrolSession
    {
        return PatrolSession::create($data + ['status' => 'in_progress', 'started_at' => now()]);
    }

    public function scanCheckpoint(array $data): CheckpointScan
    {
        $session = PatrolSession::with('route.checkpoints')->findOrFail($data['patrol_session_id']);
        $checkpoint = PatrolCheckpoint::where('patrol_route_id', $session->patrol_route_id)
            ->where('code', $data['checkpoint_code'])->firstOrFail();

        if ($session->status !== 'in_progress') {
            throw new RuntimeException('Patrol session is not active.');
        }

        return CheckpointScan::create([
            'tenant_id' => $session->tenant_id,
            'patrol_session_id' => $session->id,
            'patrol_checkpoint_id' => $checkpoint->id,
            'guard_id' => $session->guard_id,
            'scanned_at' => now(),
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'notes' => $data['notes'] ?? null,
            'status' => 'valid',
        ]);
    }

    public function completeIfAllScanned(PatrolSession $session): PatrolSession
    {
        $required = $session->route()->withCount('checkpoints')->first()->checkpoints_count;
        $scanned = $session->scans()->distinct('patrol_checkpoint_id')->count('patrol_checkpoint_id');
        if ($required > 0 && $scanned >= $required) {
            $session->update(['status' => 'completed', 'completed_at' => now()]);
        }
        return $session->fresh();
    }
}
