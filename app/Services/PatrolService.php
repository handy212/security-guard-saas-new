<?php

namespace App\Services;

use App\Models\CheckpointScan;
use App\Models\PatrolCheckpoint;
use App\Models\PatrolSession;
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

        $this->enforceSequence($session, $checkpoint);

        $scan = CheckpointScan::create([
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

        $this->updateCompletion($session);

        return $scan;
    }

    public function completeIfAllScanned(PatrolSession $session): PatrolSession
    {
        return $this->updateCompletion($session);
    }

    public function updateCompletion(PatrolSession $session): PatrolSession
    {
        $required = $session->route()->withCount('checkpoints')->first()->checkpoints_count ?? 0;
        $scanned = $session->scans()->distinct('patrol_checkpoint_id')->count('patrol_checkpoint_id');
        $percent = $required > 0 ? (int) min(100, round(($scanned / $required) * 100)) : 0;

        $updates = ['completion_percent' => $percent];
        if ($required > 0 && $scanned >= $required) {
            $updates['status'] = 'completed';
            $updates['completed_at'] = now();
        }

        $session->update($updates);

        return $session->fresh();
    }

    private function enforceSequence(PatrolSession $session, PatrolCheckpoint $checkpoint): void
    {
        $lastSequence = $session->scans()
            ->join('patrol_checkpoints', 'patrol_checkpoints.id', '=', 'checkpoint_scans.patrol_checkpoint_id')
            ->max('patrol_checkpoints.sequence') ?? 0;

        if ($checkpoint->sequence > $lastSequence + 1) {
            throw new RuntimeException('Checkpoint scanned out of required sequence.');
        }
    }
}
