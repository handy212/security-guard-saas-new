<?php

namespace App\Services;

use App\Models\CheckpointScan;
use App\Models\Guard;
use App\Models\PatrolCheckpoint;
use App\Models\PatrolRoute;
use App\Models\PatrolSession;
use App\Models\ShiftAssignment;
use RuntimeException;

class PatrolService
{
    public function startSession(array $data): PatrolSession
    {
        return PatrolSession::create($data + ['status' => 'in_progress', 'started_at' => now()]);
    }

    /**
     * Ops-assigned patrol: create an in-progress session for a guard on a route.
     * Ensures a shift assignment exists (schema requires it).
     */
    public function assignAndStart(int $tenantId, int $routeId, int $guardId, ?int $shiftAssignmentId = null): PatrolSession
    {
        $active = PatrolSession::query()
            ->where('tenant_id', $tenantId)
            ->where('guard_id', $guardId)
            ->where('status', 'in_progress')
            ->exists();

        if ($active) {
            throw new RuntimeException('Guard already has an active patrol session.');
        }

        $route = PatrolRoute::where('tenant_id', $tenantId)->findOrFail($routeId);
        $guard = Guard::where('tenant_id', $tenantId)->findOrFail($guardId);

        $assignmentId = $shiftAssignmentId
            ?? $this->resolveAssignmentForPatrol($tenantId, $guard, $route)?->id;

        if (! $assignmentId) {
            throw new RuntimeException('Guard needs an active shift assignment on this site before starting a patrol. Deploy them first.');
        }

        return $this->startSession([
            'tenant_id' => $tenantId,
            'patrol_route_id' => $routeId,
            'guard_id' => $guardId,
            'shift_assignment_id' => $assignmentId,
        ]);
    }

    private function resolveAssignmentForPatrol(int $tenantId, Guard $guard, PatrolRoute $route): ?ShiftAssignment
    {
        return ShiftAssignment::query()
            ->where('tenant_id', $tenantId)
            ->where('guard_id', $guard->id)
            ->whereNotIn('status', ['cancelled', 'completed', 'no_show'])
            ->whereHas('shift', function ($q) use ($route) {
                $q->where('site_id', $route->site_id)
                    ->where('starts_at', '<=', now()->addHours(2))
                    ->where('ends_at', '>=', now()->subHour())
                    ->whereNotIn('status', ['cancelled', 'completed']);
            })
            ->latest('id')
            ->first();
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
