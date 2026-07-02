<?php

namespace App\Livewire\Scheduling;

use App\Models\ShiftAssignment;
use App\Models\ShiftConfirmation;
use App\Services\WorkforceService;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class ShiftStatusIndex extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';

    public function confirmShift(int $confirmationId, WorkforceService $service): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $service->confirmShift(ShiftConfirmation::findOrFail($confirmationId));
        session()->flash('status', 'Shift confirmed.');
    }

    public function render()
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $tenantId = TenantContext::id();

        return view('livewire.scheduling.shift-status-index', [
            'confirmations' => ShiftConfirmation::with(['assignedGuard', 'shiftAssignment.shift.site'])
                ->where('tenant_id', $tenantId)
                ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->paginate(20),
            'assignments' => ShiftAssignment::with(['assignedGuard', 'shift.site'])
                ->where('tenant_id', $tenantId)
                ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->limit(30)
                ->get(),
            'statuses' => config('scheduling.assignment_statuses'),
        ])->layout('layouts.app');
    }
}
