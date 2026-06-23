<?php

namespace App\Livewire\Guard;

use App\Models\AttendanceLog;
use App\Models\PatrolRoute;
use App\Models\PatrolSession;
use App\Models\ShiftAssignment;
use App\Services\AttendanceService;
use App\Services\DispatchService;
use App\Services\GuardLocationService;
use App\Services\OfflineSyncService;
use App\Services\PatrolService;
use App\Support\TenantContext;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;

class MobileDashboard extends Component
{
    public float $latitude = 0;

    public float $longitude = 0;

    public ?int $activeAssignmentId = null;

    public ?int $activeAttendanceId = null;

    public string $checkpointCode = '';

    public ?int $patrolSessionId = null;

    public string $statusMessage = '';

    public bool $showScanner = false;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('mobile.use'), 403);

        $guardId = auth()->user()->guardProfile?->id;
        if ($guardId) {
            $this->activeAttendanceId = AttendanceLog::query()
                ->where('guard_id', $guardId)
                ->whereNull('clock_out_at')
                ->latest()
                ->value('id');

            $this->activeAssignmentId = ShiftAssignment::query()
                ->where('guard_id', $guardId)
                ->where('tenant_id', TenantContext::id())
                ->latest()
                ->value('id');
        }
    }

    #[On('qr-scanned')]
    public function onQrScanned(string $code): void
    {
        $this->checkpointCode = $code;
        $this->showScanner = false;
        $this->statusMessage = 'QR code captured: '.$code;
    }

    public function toggleScanner(): void
    {
        $this->showScanner = ! $this->showScanner;
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

    public function scanCheckpoint(PatrolService $patrol): void
    {
        $this->validate(['checkpointCode' => 'required|string', 'patrolSessionId' => 'required|integer']);
        [$lat, $lng] = $this->coordinates();

        try {
            $patrol->scanCheckpoint([
                'patrol_session_id' => $this->patrolSessionId,
                'checkpoint_code' => $this->checkpointCode,
                'latitude' => $lat,
                'longitude' => $lng,
            ]);
            $this->checkpointCode = '';
            $this->statusMessage = 'Checkpoint scanned.';
        } catch (RuntimeException $e) {
            $this->addError('action', $e->getMessage());
        }
    }

    public function startPatrol(int $routeId, PatrolService $patrol): void
    {
        $guardId = auth()->user()->guardProfile?->id;
        abort_unless($guardId, 403);

        $session = $patrol->startSession([
            'tenant_id' => TenantContext::id(),
            'patrol_route_id' => $routeId,
            'guard_id' => $guardId,
            'shift_assignment_id' => $this->activeAssignmentId,
        ]);

        $this->patrolSessionId = $session->id;
        $this->statusMessage = 'Patrol started — session #'.$session->id;
    }

    public function syncOfflineQueue(array $items, OfflineSyncService $offline): void
    {
        if (empty($items)) {
            return;
        }

        $batch = $offline->queue([
            'tenant_id' => TenantContext::id(),
            'user_id' => auth()->id(),
            'payload' => $items,
        ]);

        $result = $offline->process($batch);
        $processed = count($result->result['processed'] ?? []);
        $this->statusMessage = $processed.' offline action(s) synced.';
    }

    public function render()
    {
        $guardId = auth()->user()->guardProfile?->id;
        $tenantId = TenantContext::id();

        $assignments = ShiftAssignment::with(['shift.site'])
            ->where('tenant_id', $tenantId)
            ->when($guardId, fn ($q) => $q->where('guard_id', $guardId))
            ->latest()
            ->limit(10)
            ->get();

        $siteIds = $assignments->pluck('shift.site_id')->filter()->unique();

        return view('livewire.guard.mobile-dashboard', [
            'assignments' => $assignments,
            'activePatrols' => PatrolSession::with('route')
                ->where('tenant_id', $tenantId)
                ->when($guardId, fn ($q) => $q->where('guard_id', $guardId))
                ->where('status', 'in_progress')
                ->latest()
                ->get(),
            'patrolRoutes' => PatrolRoute::with('site')
                ->where('tenant_id', $tenantId)
                ->when($siteIds->isNotEmpty(), fn ($q) => $q->whereIn('site_id', $siteIds))
                ->where('status', 'active')
                ->get(),
        ])->layout('layouts.guard');
    }

    private function ownedAssignment(): ShiftAssignment
    {
        return ShiftAssignment::query()
            ->where('id', $this->activeAssignmentId ?? 0)
            ->where('guard_id', auth()->user()->guardProfile?->id)
            ->where('tenant_id', TenantContext::id())
            ->firstOrFail();
    }

    private function coordinates(): array
    {
        return [
            $this->latitude ?: 6.206,
            $this->longitude ?: -1.665,
        ];
    }
}
