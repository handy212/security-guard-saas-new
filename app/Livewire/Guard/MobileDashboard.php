<?php

namespace App\Livewire\Guard;

use App\Models\AttendanceLog;
use App\Models\DispatchEvent;
use App\Models\Guard;
use App\Models\PassdownLog;
use App\Models\PatrolRoute;
use App\Models\PatrolSession;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Services\AttendanceService;
use App\Services\CustomReportService;
use App\Services\DispatchService;
use App\Services\EnterpriseScheduleService;
use App\Services\GuardLocationService;
use App\Services\OfflineSyncService;
use App\Services\PatrolService;
use App\Services\WorkforceService;
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

    public bool $showNfcScanner = false;

    public ?int $activeReportTemplateId = null;

    public array $reportData = [];

    public string $passdownContent = '';

    public string $swapReason = '';

    public ?int $swapReplacementGuardId = null;

    public array $bidNotes = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('mobile.use'), 403);

        $guardId = auth()->user()->guardProfile?->id;
        if (! $guardId) {
            return;
        }

        $this->activeAttendanceId = AttendanceLog::query()
            ->where('guard_id', $guardId)
            ->whereNull('clock_out_at')
            ->latest()
            ->value('id');

        $this->activeAssignmentId = ShiftAssignment::with('shift')
            ->where('guard_id', $guardId)
            ->where('tenant_id', TenantContext::id())
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->whereHas('shift', fn ($q) => $q->whereDate('starts_at', today()))
            ->get()
            ->sortBy(fn (ShiftAssignment $a) => $a->shift?->starts_at)
            ->first()
            ?->id;
    }

    #[On('qr-scanned')]
    public function onQrScanned(string $code): void
    {
        $this->checkpointCode = $code;
        $this->showScanner = false;
        $this->statusMessage = 'QR code captured: '.$code;
    }

    #[On('nfc-scanned')]
    public function onNfcScanned(string $code): void
    {
        $this->checkpointCode = $code;
        $this->statusMessage = 'NFC tag captured: '.$code;
    }

    public function saveReportDraft(CustomReportService $reports): void
    {
        $guardId = auth()->user()->guardProfile?->id;
        abort_unless($guardId && $this->activeReportTemplateId, 422);
        $siteId = $this->ownedAssignment()->shift->site_id;
        $reports->saveDraft($this->activeReportTemplateId, $guardId, $siteId, $this->reportData, $this->activeAssignmentId);
        $this->statusMessage = 'Report draft saved.';
    }

    public function submitCustomReport(CustomReportService $reports): void
    {
        $guardId = auth()->user()->guardProfile?->id;
        abort_unless($guardId && $this->activeReportTemplateId, 422);
        $siteId = $this->ownedAssignment()->shift->site_id;
        $submission = $reports->saveDraft($this->activeReportTemplateId, $guardId, $siteId, $this->reportData, $this->activeAssignmentId);
        $reports->submit($submission);
        $this->reportData = [];
        $this->statusMessage = 'Custom report submitted.';
    }

    public function savePassdown(): void
    {
        $this->validate(['passdownContent' => 'required|string|min:10']);
        $assignment = $this->ownedAssignment();
        PassdownLog::create([
            'tenant_id' => TenantContext::id(),
            'site_id' => $assignment->shift->site_id,
            'guard_id' => $assignment->guard_id,
            'shift_assignment_id' => $assignment->id,
            'content' => $this->passdownContent,
        ]);
        $this->passdownContent = '';
        $this->statusMessage = 'Passdown saved.';
    }

    public function confirmMyShift(WorkforceService $workforce): void
    {
        $assignment = $this->ownedAssignment();
        $confirmation = $workforce->requestConfirmation($assignment);
        $workforce->confirmShift($confirmation);
        $this->statusMessage = 'Shift confirmed.';
    }

    public function bidOnShift(int $shiftId, EnterpriseScheduleService $enterprise): void
    {
        $guard = auth()->user()->guardProfile;
        abort_unless($guard, 403);

        try {
            $shift = Shift::query()
                ->where('tenant_id', TenantContext::id())
                ->findOrFail($shiftId);

            $enterprise->bidForOpenShift($shift, $guard, filled($this->bidNotes[$shiftId] ?? null) ? $this->bidNotes[$shiftId] : null);
            unset($this->bidNotes[$shiftId]);
            $this->statusMessage = 'Bid submitted for '.$shift->title.'.';
        } catch (RuntimeException $e) {
            $this->addError('action', $e->getMessage());
        }
    }

    public function requestShiftSwap(EnterpriseScheduleService $enterprise): void
    {
        $guard = auth()->user()->guardProfile;
        abort_unless($guard, 403);

        $replacement = $this->swapReplacementGuardId
            ? Guard::query()
                ->where('tenant_id', TenantContext::id())
                ->where('id', $this->swapReplacementGuardId)
                ->where('id', '!=', $guard->id)
                ->first()
            : null;

        try {
            $enterprise->requestSwap(
                $this->ownedAssignment(),
                $guard,
                $replacement,
                $this->swapReason ?: null,
            );
            $this->swapReason = '';
            $this->swapReplacementGuardId = null;
            $this->statusMessage = 'Shift swap request submitted.';
        } catch (RuntimeException $e) {
            $this->addError('action', $e->getMessage());
        }
    }

    public function advanceDispatch(int $dispatchId, DispatchService $dispatch): void
    {
        $guardId = auth()->user()->guardProfile?->id;
        abort_unless($guardId, 403);

        $event = DispatchEvent::query()
            ->where('id', $dispatchId)
            ->where('guard_id', $guardId)
            ->where('tenant_id', TenantContext::id())
            ->firstOrFail();

        $dispatch->advanceStatus($event, auth()->id());
        $this->statusMessage = 'Dispatch status: '.$event->fresh()->status->label();
    }

    public function toggleScanner(): void
    {
        $this->showScanner = ! $this->showScanner;
        if ($this->showScanner) {
            $this->showNfcScanner = false;
        }
    }

    public function toggleNfcScanner(): void
    {
        $this->showNfcScanner = ! $this->showNfcScanner;
        if ($this->showNfcScanner) {
            $this->showScanner = false;
        }
    }

    public function clockIn(AttendanceService $attendance): void
    {
        try {
            $assignment = $this->ownedAssignment();
            [$lat, $lng] = $this->coordinates();
            $log = $attendance->clockIn($assignment->id, $lat, $lng);
            $this->activeAttendanceId = $log->id;
            $this->statusMessage = 'Clocked in at '.now()->format('H:i');
        } catch (RuntimeException $e) {
            $this->addError('action', $e->getMessage());
        }
    }

    public function clockOut(AttendanceService $attendance): void
    {
        if (! $this->activeAttendanceId) {
            $this->addError('action', 'No active clock-in found.');

            return;
        }

        try {
            [$lat, $lng] = $this->coordinates();
            $attendance->clockOut($this->activeAttendanceId, $lat, $lng);
            $this->activeAttendanceId = null;
            $this->statusMessage = 'Clocked out.';
        } catch (RuntimeException $e) {
            $this->addError('action', $e->getMessage());
        }
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

        $assignments = $guardId
            ? ShiftAssignment::with(['shift.site'])
                ->where('tenant_id', $tenantId)
                ->where('guard_id', $guardId)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->whereHas('shift', fn ($q) => $q->whereDate('starts_at', today()))
                ->get()
                ->sortBy(fn (ShiftAssignment $a) => $a->shift?->starts_at)
                ->values()
            : collect();

        $siteIds = $assignments->pluck('shift.site_id')->filter()->unique();
        $customReports = app(CustomReportService::class);
        $reportTemplates = $siteIds->isNotEmpty()
            ? $customReports->templatesForSite($siteIds->first())
            : collect();

        $dispatches = $guardId
            ? app(DispatchService::class)->myActiveDispatches($guardId)
            : collect();

        $guard = auth()->user()->guardProfile;
        $enterprise = app(EnterpriseScheduleService::class);

        return view('livewire.guard.mobile-dashboard', [
            'hasGuardProfile' => (bool) $guardId,
            'isOnDuty' => (bool) $this->activeAttendanceId,
            'assignments' => $assignments,
            'dispatches' => $dispatches,
            'openShifts' => $guard ? $enterprise->openShiftsForGuard($guard) : collect(),
            'myBids' => $guard ? $enterprise->guardBids($guard) : collect(),
            'mySwaps' => $guard ? $enterprise->guardSwapRequests($guard) : collect(),
            'colleagueGuards' => Guard::query()
                ->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->when($guardId, fn ($q) => $q->where('id', '!=', $guardId))
                ->orderBy('first_name')
                ->get(),
            'reportTemplates' => $reportTemplates,
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
