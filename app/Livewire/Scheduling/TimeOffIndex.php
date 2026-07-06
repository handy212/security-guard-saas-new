<?php

namespace App\Livewire\Scheduling;

use App\Enums\LeaveStatus;
use App\Models\Guard;
use App\Models\GuardAvailability;
use App\Models\LeaveRequest;
use App\Services\LeaveSchedulingService;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use App\Support\EnumHelper;
use Livewire\Component;
use Livewire\WithPagination;

class TimeOffIndex extends Component
{
    use WithPagination;

    public array $leaveForm = ['guard_id' => '', 'type' => 'annual', 'starts_on' => '', 'ends_on' => '', 'reason' => ''];

    public array $availabilityForm = ['guard_id' => '', 'weekday' => 1, 'starts_at' => '08:00', 'ends_at' => '17:00', 'is_available' => true];

    public ?int $editingLeaveId = null;

    public ?int $editingAvailabilityId = null;

    public string $leaveFilter = 'pending';

    public string $guardFilter = '';

    public string $availabilityGuardFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
    }

    public function updatedLeaveFilter(): void
    {
        $this->resetPage();
    }

    public function updatedGuardFilter(): void
    {
        $this->resetPage();
    }

    public function submitLeave(): void
    {
        $data = $this->validate([
            'leaveForm.guard_id' => ['required', TenantValidation::exists('guards')],
            'leaveForm.type' => 'required|in:'.implode(',', array_keys(config('scheduling.leave_types'))),
            'leaveForm.starts_on' => 'required|date',
            'leaveForm.ends_on' => 'required|date|after_or_equal:leaveForm.starts_on',
            'leaveForm.reason' => 'nullable|string|max:1000',
        ])['leaveForm'];

        if ($this->editingLeaveId) {
            $request = LeaveRequest::where('tenant_id', TenantContext::id())->findOrFail($this->editingLeaveId);
            abort_unless($request->status === LeaveStatus::PENDING, 422, 'Only pending requests can be edited.');

            $request->update($data);
            session()->flash('status', 'Time off request updated.');
        } else {
            LeaveRequest::create($data + [
                'tenant_id' => TenantContext::id(),
                'status' => LeaveStatus::PENDING,
            ]);
            session()->flash('status', 'Time off request submitted.');
        }

        $this->resetLeaveForm();
    }

    public function editLeave(int $requestId): void
    {
        $request = LeaveRequest::where('tenant_id', TenantContext::id())->findOrFail($requestId);
        abort_unless($request->status === LeaveStatus::PENDING, 422, 'Only pending requests can be edited.');

        $this->editingLeaveId = $request->id;
        $this->leaveForm = [
            'guard_id' => (string) $request->guard_id,
            'type' => $request->type,
            'starts_on' => $request->starts_on?->format('Y-m-d') ?? '',
            'ends_on' => $request->ends_on?->format('Y-m-d') ?? '',
            'reason' => $request->reason ?? '',
        ];
    }

    public function cancelLeaveEdit(): void
    {
        $this->resetLeaveForm();
    }

    public function cancelLeave(int $requestId): void
    {
        $request = LeaveRequest::where('tenant_id', TenantContext::id())->findOrFail($requestId);
        abort_unless($request->status === LeaveStatus::PENDING, 422, 'Only pending requests can be cancelled.');

        $request->update(['status' => LeaveStatus::CANCELLED]);
        session()->flash('status', 'Time off request cancelled.');
    }

    public function approveLeave(int $requestId, LeaveSchedulingService $leaves): void
    {
        $request = LeaveRequest::with('assignedGuard')
            ->where('tenant_id', TenantContext::id())
            ->findOrFail($requestId);

        $conflicts = $leaves->overlappingShiftsForLeave($request);
        if ($conflicts->isNotEmpty()) {
            session()->flash('error', "Cannot approve: {$conflicts->count()} scheduled shift(s) overlap this leave. Unassign the guard or adjust dates first.");

            return;
        }

        $request->update([
            'status' => LeaveStatus::APPROVED,
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);
        session()->flash('status', 'Time off approved.');
    }

    public function rejectLeave(int $requestId): void
    {
        LeaveRequest::where('tenant_id', TenantContext::id())->findOrFail($requestId)->update(['status' => LeaveStatus::REJECTED]);
        session()->flash('status', 'Time off rejected.');
    }

    public function saveAvailability(): void
    {
        $data = $this->validate([
            'availabilityForm.guard_id' => ['required', TenantValidation::exists('guards')],
            'availabilityForm.weekday' => 'required|integer|min:0|max:6',
            'availabilityForm.starts_at' => 'required|date_format:H:i',
            'availabilityForm.ends_at' => 'required|date_format:H:i',
            'availabilityForm.is_available' => 'boolean',
        ])['availabilityForm'];

        if ($this->editingAvailabilityId) {
            GuardAvailability::where('tenant_id', TenantContext::id())
                ->findOrFail($this->editingAvailabilityId)
                ->update($data);
            session()->flash('status', 'Availability updated.');
        } else {
            GuardAvailability::create($data + ['tenant_id' => TenantContext::id()]);
            session()->flash('status', 'Availability saved.');
        }

        $this->resetAvailabilityForm();
    }

    public function editAvailability(int $availabilityId): void
    {
        $availability = GuardAvailability::where('tenant_id', TenantContext::id())->findOrFail($availabilityId);

        $this->editingAvailabilityId = $availability->id;
        $this->availabilityForm = [
            'guard_id' => (string) $availability->guard_id,
            'weekday' => (int) $availability->weekday,
            'starts_at' => substr((string) $availability->starts_at, 0, 5),
            'ends_at' => substr((string) $availability->ends_at, 0, 5),
            'is_available' => (bool) $availability->is_available,
        ];
    }

    public function cancelAvailabilityEdit(): void
    {
        $this->resetAvailabilityForm();
    }

    public function deleteAvailability(int $availabilityId): void
    {
        GuardAvailability::where('tenant_id', TenantContext::id())->whereKey($availabilityId)->delete();
        session()->flash('status', 'Availability removed.');
    }

    public function render(LeaveSchedulingService $leaves)
    {
        $tenantId = TenantContext::id();

        $leaveQuery = LeaveRequest::with(['assignedGuard', 'approver'])
            ->where('tenant_id', $tenantId)
            ->when($this->leaveFilter !== 'all', fn ($q) => $q->where('status', $this->leaveFilter))
            ->when(filled($this->guardFilter), fn ($q) => $q->where('guard_id', $this->guardFilter))
            ->latest();

        $leaveRequests = $leaveQuery->paginate(15);

        $conflictMeta = $leaveRequests->getCollection()->mapWithKeys(function (LeaveRequest $request) use ($leaves) {
            if (! EnumHelper::is($request->status, 'pending')) {
                return [$request->id => ['count' => 0, 'scheduleUrl' => null]];
            }

            $overlaps = $leaves->overlappingShiftsForLeave($request);
            $firstShift = $overlaps->first()?->shift;

            return [$request->id => [
                'count' => $overlaps->count(),
                'scheduleUrl' => $firstShift
                    ? route('schedules.index', ['date' => $firstShift->starts_at->toDateString()])
                    : null,
            ]];
        });

        $availabilities = GuardAvailability::with('assignedGuard')
            ->where('tenant_id', $tenantId)
            ->when(filled($this->availabilityGuardFilter), fn ($q) => $q->where('guard_id', $this->availabilityGuardFilter))
            ->orderBy('weekday')
            ->orderBy('starts_at')
            ->get();

        $pendingCount = LeaveRequest::where('tenant_id', $tenantId)->where('status', LeaveStatus::PENDING)->count();

        return view('livewire.scheduling.time-off-index', [
            'guards' => Guard::where('tenant_id', $tenantId)->orderBy('first_name')->get(),
            'leaveRequests' => $leaveRequests,
            'conflictMeta' => $conflictMeta,
            'availabilities' => $availabilities,
            'leaveTypes' => config('scheduling.leave_types'),
            'leaveStatuses' => config('scheduling.leave_statuses'),
            'weekdays' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
            'pendingCount' => $pendingCount,
        ])->layout('layouts.app');
    }

    private function resetLeaveForm(): void
    {
        $this->editingLeaveId = null;
        $this->leaveForm = ['guard_id' => '', 'type' => 'annual', 'starts_on' => '', 'ends_on' => '', 'reason' => ''];
    }

    private function resetAvailabilityForm(): void
    {
        $this->editingAvailabilityId = null;
        $this->availabilityForm = ['guard_id' => '', 'weekday' => 1, 'starts_at' => '08:00', 'ends_at' => '17:00', 'is_available' => true];
    }
}
