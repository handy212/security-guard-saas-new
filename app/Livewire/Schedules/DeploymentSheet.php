<?php

namespace App\Livewire\Schedules;

use App\Models\Guard;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftConfirmation;
use App\Models\Site;
use App\Services\ScheduleService;
use App\Services\WorkforceService;
use App\Support\EnumHelper;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Carbon\Carbon;
use Livewire\Component;
use RuntimeException;

class DeploymentSheet extends Component
{
    public string $date = '';

    public string $siteFilter = 'all';

    public array $pendingGuard = [];

    public ?int $reassignAssignmentId = null;

    public string $reassignGuardId = '';

    protected $queryString = [
        'date' => ['except' => ''],
        'siteFilter' => ['except' => 'all', 'as' => 'site'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $this->date = $this->date ?: today()->toDateString();
    }

    public function previousDay(): void
    {
        $this->date = Carbon::parse($this->date)->subDay()->toDateString();
    }

    public function nextDay(): void
    {
        $this->date = Carbon::parse($this->date)->addDay()->toDateString();
    }

    public function goToday(): void
    {
        $this->date = today()->toDateString();
    }

    public function assignToShift(int $shiftId, ScheduleService $service): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);

        $this->validate([
            "pendingGuard.{$shiftId}" => ['required', TenantValidation::exists('guards')],
        ], [
            "pendingGuard.{$shiftId}.required" => 'Select a guard before assigning.',
        ]);

        $shift = Shift::where('tenant_id', TenantContext::id())->findOrFail($shiftId);

        try {
            $service->assignGuard($shift, Guard::findOrFail((int) $this->pendingGuard[$shiftId]));
        } catch (RuntimeException $e) {
            $this->addError("pendingGuard.{$shiftId}", $e->getMessage());

            return;
        }

        unset($this->pendingGuard[$shiftId]);
        session()->flash('status', 'Guard assigned.');
    }

    public function confirmAssignment(int $assignmentId, WorkforceService $workforce): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $assignment = ShiftAssignment::where('tenant_id', TenantContext::id())->findOrFail($assignmentId);
        $confirmation = ShiftConfirmation::where('shift_assignment_id', $assignment->id)->first();

        if (! $confirmation) {
            $confirmation = $workforce->requestConfirmation($assignment);
        }

        if (EnumHelper::value($confirmation->status) === 'pending') {
            $workforce->confirmShift($confirmation);
            session()->flash('status', 'Assignment confirmed.');
        }
    }

    public function unassign(int $assignmentId, ScheduleService $service): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $assignment = ShiftAssignment::with('shift')
            ->where('tenant_id', TenantContext::id())
            ->findOrFail($assignmentId);
        $service->unassignGuard($assignment);
        session()->flash('status', 'Guard unassigned.');
    }

    public function openReassign(int $assignmentId): void
    {
        $this->reassignAssignmentId = $assignmentId;
        $this->reassignGuardId = '';
        $this->resetErrorBag();
    }

    public function reassign(ScheduleService $service): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);

        $this->validate([
            'reassignGuardId' => ['required', TenantValidation::exists('guards')],
        ]);

        $assignment = ShiftAssignment::with('shift')
            ->where('tenant_id', TenantContext::id())
            ->findOrFail($this->reassignAssignmentId);

        $shift = $assignment->shift;
        $service->unassignGuard($assignment);

        try {
            $service->assignGuard($shift, Guard::findOrFail((int) $this->reassignGuardId));
        } catch (RuntimeException $e) {
            $this->addError('reassignGuardId', $e->getMessage());

            return;
        }

        $this->reassignAssignmentId = null;
        $this->reassignGuardId = '';
        session()->flash('status', 'Guard reassigned.');
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        $assignments = ShiftAssignment::with(['shift.site', 'shift.sitePost', 'assignedGuard', 'confirmations'])
            ->where('tenant_id', $tenantId)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->whereHas('shift', function ($q) {
                $q->whereDate('starts_at', $this->date)->whereNotIn('status', ['cancelled', 'completed']);
                if ($this->siteFilter !== 'all') {
                    $q->where('site_id', $this->siteFilter);
                }
            })
            ->get()
            ->sortBy(fn ($a) => $a->shift?->starts_at);

        $understaffed = Shift::with(['site', 'sitePost', 'assignments.assignedGuard'])
            ->where('tenant_id', $tenantId)
            ->whereDate('starts_at', $this->date)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->when($this->siteFilter !== 'all', fn ($q) => $q->where('site_id', $this->siteFilter))
            ->get()
            ->filter(function (Shift $shift) {
                $staffed = $shift->assignments
                    ->filter(fn ($a) => ! in_array(EnumHelper::value($a->status), ['cancelled', 'no_show'], true))
                    ->count();

                return $staffed < (int) $shift->required_guards;
            })
            ->values();

        $sites = Site::where('tenant_id', $tenantId)->orderBy('name')->get();
        $guards = Guard::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('verification_status', 'verified')
            ->orderBy('first_name')
            ->get();

        $confirmed = $assignments->filter(fn ($a) => EnumHelper::value($a->status) === 'confirmed' || EnumHelper::value($a->status) === 'in_progress')->count();
        $pending = $assignments->filter(fn ($a) => EnumHelper::value($a->status) === 'assigned')->count();

        return view('livewire.schedules.deployment-sheet', [
            'assignments' => $assignments,
            'understaffed' => $understaffed,
            'sites' => $sites,
            'guards' => $guards,
            'stats' => [
                'assignments' => $assignments->count(),
                'sites' => $assignments->pluck('shift.site')->filter()->unique('id')->count(),
                'guards' => $assignments->pluck('assignedGuard')->filter()->unique('id')->count(),
                'pending' => $pending,
                'confirmed' => $confirmed,
                'gaps' => $understaffed->count(),
            ],
        ])->layout('layouts.app');
    }
}
