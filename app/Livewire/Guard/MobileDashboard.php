<?php

namespace App\Livewire\Guard;

use App\Models\ShiftAssignment;
use App\Services\AttendanceService;
use App\Services\DispatchService;
use App\Services\GuardLocationService;
use App\Support\TenantContext;
use Livewire\Component;

class MobileDashboard extends Component
{
    public float $latitude = 0;

    public float $longitude = 0;

    public ?int $activeAssignmentId = null;

    public ?int $activeAttendanceId = null;

    public string $checkpointCode = '';

    public ?int $patrolSessionId = null;

    public string $statusMessage = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('mobile.use'), 403);
    }

    public function clockIn(AttendanceService $attendance): void
    {
        $assignment = $this->ownedAssignment();
        [$lat, $lng] = $this->coordinates();
        $log = $attendance->clockIn($assignment->id, $lat, $lng);
        $this->activeAttendanceId = $log->id;
        $this->statusMessage = 'Clocked in at '.now()->format('H:i');
    }

    public function clockOut(AttendanceService $attendance): void
    {
        abort_unless($this->activeAttendanceId, 422);
        [$lat, $lng] = $this->coordinates();
        $attendance->clockOut($this->activeAttendanceId, $lat, $lng);
        $this->activeAttendanceId = null;
        $this->statusMessage = 'Clocked out.';
    }

    public function raiseSos(DispatchService $dispatch): void
    {
        [$lat, $lng] = $this->coordinates();
        $dispatch->raiseSos(auth()->user(), [
            'latitude' => $lat,
            'longitude' => $lng,
            'message' => 'SOS from guard mobile app',
        ]);
        $this->statusMessage = 'SOS sent to control room.';
    }

    public function updateLocation(GuardLocationService $locations): void
    {
        [$lat, $lng] = $this->coordinates();
        $locations->record(auth()->user(), $lat, $lng);
        $this->statusMessage = 'Location updated.';
    }

    public function scanCheckpoint(\App\Services\PatrolService $patrol): void
    {
        $this->validate(['checkpointCode' => 'required|string', 'patrolSessionId' => 'required|integer']);
        [$lat, $lng] = $this->coordinates();
        $patrol->scanCheckpoint([
            'patrol_session_id' => $this->patrolSessionId,
            'checkpoint_code' => $this->checkpointCode,
            'latitude' => $lat,
            'longitude' => $lng,
        ]);
        $this->checkpointCode = '';
        $this->statusMessage = 'Checkpoint scanned.';
    }

    public function render()
    {
        $guardId = auth()->user()->guardProfile?->id;
        $assignments = ShiftAssignment::with(['shift.site'])
            ->where('tenant_id', TenantContext::id())
            ->when($guardId, fn ($q) => $q->where('guard_id', $guardId))
            ->latest()
            ->limit(10)
            ->get();

        return view('livewire.guard.mobile-dashboard', [
            'assignments' => $assignments,
        ])->layout('layouts.guard');
    }

    private function ownedAssignment(): ShiftAssignment
    {
        $assignment = ShiftAssignment::query()
            ->where('id', $this->activeAssignmentId ?? 0)
            ->where('guard_id', auth()->user()->guardProfile?->id)
            ->where('tenant_id', TenantContext::id())
            ->firstOrFail();

        return $assignment;
    }

    private function coordinates(): array
    {
        return [
            $this->latitude ?: 6.206,
            $this->longitude ?: -1.665,
        ];
    }
}
