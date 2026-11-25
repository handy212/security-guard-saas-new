<?php

namespace App\Livewire\Guard;

use App\Enums\GuardDutyType;
use App\Models\AttendanceLog;
use App\Models\DispatchEvent;
use App\Models\DispatchTeam;
use App\Models\Guard;
use App\Models\PassdownLog;
use App\Models\PatrolRoute;
use App\Models\PatrolSession;
use App\Models\PostOrder;
use App\Models\ReportTemplate;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\SosAlert;
use App\Services\AttendanceService;
use App\Services\CustomReportService;
use App\Services\DispatchService;
use App\Services\EnterpriseScheduleService;
use App\Services\GuardLocationService;
use App\Services\OfflineSyncService;
use App\Services\PatrolService;
use App\Services\WorkforceService;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use RuntimeException;

class MobileDashboard extends Component
{
    public ?float $latitude = null;

    public ?float $longitude = null;

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

        $attendance = AttendanceLog::forTenant(TenantContext::id())
            ->where('guard_id', $guardId)
            ->whereNull('clock_out_at')
            ->latest()
            ->first(['id', 'shift_assignment_id']);
        $this->activeAttendanceId = $attendance?->id;

        $assignments = $this->scheduledAssignments($guardId)->with('shift')
            ->get()
            ->sortBy(fn (ShiftAssignment $a) => $a->shift?->starts_at);

        $this->activeAssignmentId = ($assignments->firstWhere('id', $attendance?->shift_assignment_id) ?? $assignments->first())?->id;
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
        $assignment = $this->ownedAssignment();
        $template = $this->selectedReportTemplate($reports, $assignment);
        $reports->saveDraft($template->id, $assignment->guard_id, $assignment->shift->site_id, $this->reportData, $assignment->id);
        $this->statusMessage = 'Report draft saved.';
    }

    public function submitCustomReport(CustomReportService $reports): void
    {
        $assignment = $this->ownedAssignment();
        $template = $this->selectedReportTemplate($reports, $assignment);
        $rules = $template->fields->mapWithKeys(fn ($field) => [
            'reportData.'.$field->id => [$field->is_required ? 'required' : 'nullable', 'string'],
        ])->all();
        $labels = $template->fields->mapWithKeys(fn ($field) => ['reportData.'.$field->id => $field->label])->all();
        $this->validate($rules, [], $labels);
        $submission = $reports->saveDraft($template->id, $assignment->guard_id, $assignment->shift->site_id, $this->reportData, $assignment->id);
        $reports->submit($submission);
        $this->reportData = [];
        $this->statusMessage = 'Custom report submitted.';
    }

    public function updatedActiveAssignmentId(): void
    {
        $this->activeReportTemplateId = null;
        $this->updatedActiveReportTemplateId();
    }

    public function updatedActiveReportTemplateId(): void
    {
        $this->reportData = [];
        $this->resetValidation();
    }

    private function selectedReportTemplate(CustomReportService $reports, ShiftAssignment $assignment): ReportTemplate
    {
        $template = $reports->templatesForSite($assignment->shift->site_id)->find($this->activeReportTemplateId);
        if (! $template) {
            throw ValidationException::withMessages(['action' => 'Select an available report for your shift site.']);
        }

        return $template;
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

    public array $dispatchOutcomes = [];

    public function respondToPatriotDispatch(int $dispatchId, string $decision, DispatchService $dispatch): void
    {
        abort_unless(auth()->user()->can('mobile.use'), 403);
        $event = $this->assignedDispatch($dispatchId);
        $dispatch->queuePatriotDecision($event, $decision, auth()->id());
        $this->statusMessage = 'Your response is queued for Patriot.';
    }

    public function acknowledgeDispatch(int $dispatchId, DispatchService $dispatch): void
    {
        $dispatch->acknowledgeTeamDispatch($this->assignedTeamDispatch($dispatchId), auth()->id());
        $this->statusMessage = 'Dispatch acknowledged.';
    }

    public function submitDispatchOutcome(int $dispatchId, DispatchService $dispatch): void
    {
        $event = $this->assignedDispatch($dispatchId);
        $this->validate(['dispatchOutcomes.'.$dispatchId => ['required', 'string', 'max:2000']]);
        $dispatch->recordTeamOutcome($event, $this->dispatchOutcomes[$dispatchId], auth()->id());
        unset($this->dispatchOutcomes[$dispatchId]);
        $this->statusMessage = 'Outcome recorded. Confirm when your team is back at station.';
    }

    private function assignedDispatch(int $dispatchId): DispatchEvent
    {
        $guardId = auth()->user()->guardProfile?->id;
        abort_unless($guardId, 403);

        return DispatchEvent::forTenant(TenantContext::id())->where('guard_id', $guardId)->findOrFail($dispatchId);
    }

    private function assignedTeamDispatch(int $dispatchId): DispatchEvent
    {
        $event = $this->assignedDispatch($dispatchId);
        abort_unless($event->dispatch_team_id !== null, 404);

        return $event;
    }

    public function confirmDispatchReturn(int $dispatchId, DispatchService $dispatch): void
    {
        $event = $this->assignedTeamDispatch($dispatchId);
        abort_unless($event->awaitingStationReturn(), 422);

        $dispatch->confirmStationReturn($event, auth()->id());
        $this->statusMessage = 'Return to standby station confirmed.';
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
            $log = AttendanceLog::forTenant(TenantContext::id())
                ->where('guard_id', auth()->user()->guardProfile?->id)->whereNull('clock_out_at')
                ->findOrFail($this->activeAttendanceId);
            $attendance->clockOut($log->id, $lat, $lng);
            $this->activeAttendanceId = null;
            $this->statusMessage = 'Clocked out.';
        } catch (RuntimeException $e) {
            $this->addError('action', $e->getMessage());
        }
    }

    public function raiseSos(DispatchService $dispatch): void
    {
        $coordinates = $this->validCoordinates() ? [$this->latitude, $this->longitude] : [null, null];
        [$lat, $lng] = $coordinates;
        $dispatch->raiseSos(auth()->user(), [
            'latitude' => $lat,
            'longitude' => $lng,
            'message' => 'SOS from guard mobile app',
        ]);
        $this->statusMessage = 'SOS received by GuardCore Pro. Waiting for Control to acknowledge.';
    }

    public function updateLocation(GuardLocationService $locations): void
    {
        [$lat, $lng] = $this->coordinates();
        $locations->record(auth()->user(), $lat, $lng);
        $this->statusMessage = 'Location updated.';
    }

    public function scanCheckpoint(PatrolService $patrol): void
    {
        $this->validate(
            ['checkpointCode' => 'required|string', 'patrolSessionId' => 'required|integer'],
            ['checkpointCode.required' => 'Enter or scan a checkpoint code.', 'patrolSessionId.required' => 'Select an active patrol.'],
        );
        $session = PatrolSession::forTenant(TenantContext::id())
            ->where('guard_id', auth()->user()->guardProfile?->id)
            ->where('status', 'in_progress')
            ->find($this->patrolSessionId);
        if (! $session) {
            throw ValidationException::withMessages(['patrolSessionId' => 'Select an active patrol.']);
        }
        if (! $session->route->checkpoints()->where('code', $this->checkpointCode)->exists()) {
            throw ValidationException::withMessages(['checkpointCode' => 'This checkpoint does not belong to the selected patrol route.']);
        }
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

        $tenantId = TenantContext::id();
        $route = PatrolRoute::forTenant($tenantId)->where('status', 'active')->findOrFail($routeId);
        if (! $route->checkpoints()->exists()) {
            $this->addError('action', 'This patrol route has no checkpoints. Contact your supervisor.');

            return;
        }

        $assignments = $this->scheduledAssignments($guardId)
            ->whereHas('shift', fn (Builder $query) => $query->where('site_id', $route->site_id))
            ->with('shift')
            ->get()
            ->sortBy(fn (ShiftAssignment $assignment) => $assignment->shift->starts_at);
        $matchingAssignment = $assignments->firstWhere('id', $this->activeAssignmentId) ?? $assignments->first();

        if (! $matchingAssignment) {
            $this->addError('action', 'You need a scheduled shift at this site to start a patrol.');

            return;
        }

        $session = $patrol->startSession([
            'tenant_id' => $tenantId,
            'patrol_route_id' => $routeId,
            'guard_id' => $guardId,
            'shift_assignment_id' => $matchingAssignment->id,
        ]);

        $this->patrolSessionId = $session->id;
        $this->statusMessage = 'Patrol started — session #'.$session->id;
    }

    /** @return list<int> */
    public function syncOfflineQueue(array $items, OfflineSyncService $offline): array
    {
        if (empty($items)) {
            return [];
        }

        $batch = $offline->queue([
            'tenant_id' => TenantContext::id(),
            'user_id' => auth()->id(),
            'payload' => $items,
        ]);

        $result = $offline->process($batch);
        $processed = count($result->result['processed'] ?? []);
        $failed = count($result->result['errors'] ?? []);
        $this->statusMessage = $processed.' offline action(s) synced.'.($failed ? " {$failed} could not sync and remain on this device." : '');

        return $result->result['processed_indexes'] ?? [];
    }

    public function markFieldNotificationRead(string $id): void
    {
        auth()->user()->unreadNotifications()->whereKey($id)->first()?->markAsRead();
    }

    public function render()
    {
        $guardId = auth()->user()->guardProfile?->id;
        $tenantId = TenantContext::id();

        $assignments = $guardId
            ? $this->scheduledAssignments($guardId)->with(['shift.site', 'shift.sitePost'])
                ->get()
                ->sortBy(fn (ShiftAssignment $a) => $a->shift?->starts_at)
                ->values()
            : collect();

        $selectedAssignment = $assignments->firstWhere('id', $this->activeAssignmentId);
        $selectedShift = $selectedAssignment?->shift;
        $postOrders = $selectedShift?->tenant_id === $tenantId
            ? PostOrder::publishedForShift($selectedShift)->get()
            : collect();

        $siteIds = $assignments->pluck('shift.site_id')->filter()->unique();
        $customReports = app(CustomReportService::class);
        $reportTemplates = $selectedShift?->site_id
            ? $customReports->templatesForSite($selectedShift->site_id)
            : collect();

        $dispatches = $guardId
            ? app(DispatchService::class)->myActiveDispatches($guardId)
            : collect();

        $finishedDispatches = $guardId ? DispatchEvent::forTenant($tenantId)->with('site')
            ->where('guard_id', $guardId)->whereIn('status', ['resolved', 'closed'])
            ->latest()->limit(20)->get() : collect();

        $guard = auth()->user()->guardProfile;
        $standbyTeam = $guard?->duty_type === GuardDutyType::Dispatch && $guard->dispatch_team_id
            ? DispatchTeam::forTenant($tenantId)->with('standbyPoint.region')->find($guard->dispatch_team_id)
            : null;
        $enterprise = app(EnterpriseScheduleService::class);

        return view('livewire.guard.mobile-dashboard', [
            'fieldNotifications' => auth()->user()->notifications()->latest()->limit(20)->get(),
            'hasGuardProfile' => (bool) $guardId,
            'latestSos' => $guardId ? SosAlert::forTenant($tenantId)->where('guard_id', $guardId)->latest('id')->first() : null,
            'isOnDuty' => (bool) $this->activeAttendanceId,
            'assignments' => $assignments,
            'selectedAssignment' => $selectedAssignment,
            'standbyTeam' => $standbyTeam,
            'postOrders' => $postOrders,
            'dispatches' => $dispatches,
            'finishedDispatches' => $finishedDispatches,
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
                ->withCount(['scans as verified_checkpoints' => fn ($query) => $query->where('status', 'valid')
                    ->select(DB::raw('COUNT(DISTINCT patrol_checkpoint_id)'))])
                ->where('tenant_id', $tenantId)
                ->where('guard_id', $guardId)
                ->where('status', 'in_progress')
                ->latest()
                ->get(),
            'patrolRoutes' => PatrolRoute::with('site')
                ->where('tenant_id', $tenantId)
                ->whereIn('site_id', $siteIds)
                ->where('status', 'active')
                ->has('checkpoints')
                ->get(),
        ])->layout('layouts.guard');
    }

    /** @return Builder<ShiftAssignment> */
    private function scheduledAssignments(int $guardId): Builder
    {
        return $this->eligibleAssignments($guardId)
            ->whereHas('shift', fn (Builder $query) => $query
                ->where('ends_at', '>', today())
                ->where('starts_at', '<', today()->addDay()));
    }

    /** @return Builder<ShiftAssignment> */
    private function eligibleAssignments(int $guardId): Builder
    {
        return ShiftAssignment::query()
            ->where('tenant_id', TenantContext::id())
            ->where('guard_id', $guardId)
            ->whereNotIn('status', ['cancelled', 'completed', 'no_show'])
            ->whereHas('shift', fn (Builder $query) => $query->whereNotIn('status', ['cancelled', 'completed']));
    }

    private function ownedAssignment(): ShiftAssignment
    {
        $guardId = auth()->user()->guardProfile?->id;
        $assignment = $guardId ? $this->eligibleAssignments($guardId)->with('shift')->find($this->activeAssignmentId) : null;
        if (! $assignment) {
            throw ValidationException::withMessages(['action' => 'Select a scheduled shift to continue.']);
        }

        return $assignment;
    }

    private function coordinates(): array
    {
        if (! $this->validCoordinates()) {
            throw ValidationException::withMessages(['action' => 'GPS is unavailable. Enable location access and try again.']);
        }

        return [$this->latitude, $this->longitude];
    }

    private function validCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null
            && abs($this->latitude) <= 90 && abs($this->longitude) <= 180
            && ($this->latitude !== 0.0 || $this->longitude !== 0.0);
    }
}
