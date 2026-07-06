<?php

namespace App\Livewire\Scheduling;

use App\Models\ShiftAssignment;
use App\Models\ShiftConfirmation;
use App\Services\ScheduleService;
use App\Services\WorkforceService;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class ShiftStatusIndex extends Component
{
    use WithPagination;

    public string $confirmationFilter = 'all';

    public string $assignmentFilter = 'all';

    public function updatedConfirmationFilter(): void
    {
        $this->resetPage();
    }

    public function confirmShift(int $confirmationId, WorkforceService $service): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $service->confirmShift(ShiftConfirmation::where('tenant_id', TenantContext::id())->findOrFail($confirmationId));
        session()->flash('status', 'Shift confirmed.');
    }

    public function unassignGuard(int $assignmentId, ScheduleService $service): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $assignment = ShiftAssignment::with('shift')
            ->where('tenant_id', TenantContext::id())
            ->findOrFail($assignmentId);
        $service->unassignGuard($assignment);
        session()->flash('status', 'Guard unassigned.');
    }

    public function render()
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $tenantId = TenantContext::id();

        return view('livewire.scheduling.shift-status-index', [
            'confirmations' => ShiftConfirmation::with(['assignedGuard', 'shiftAssignment.shift.site'])
                ->where('tenant_id', $tenantId)
                ->when($this->confirmationFilter !== 'all', fn ($q) => $q->where('status', $this->confirmationFilter))
                ->latest()
                ->paginate(20),
            'assignments' => ShiftAssignment::with(['assignedGuard', 'shift.site'])
                ->where('tenant_id', $tenantId)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->when($this->assignmentFilter !== 'all', fn ($q) => $q->where('status', $this->assignmentFilter))
                ->whereHas('shift', fn ($q) => $q->where('starts_at', '>=', now()->subDays(7)))
                ->latest()
                ->limit(40)
                ->get(),
            'confirmationStatuses' => ['all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed'],
            'assignmentStatuses' => collect(config('scheduling.assignment_statuses'))->prepend('All', 'all'),
            'pendingConfirmationCount' => ShiftConfirmation::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
        ])->layout('layouts.app');
    }
}
