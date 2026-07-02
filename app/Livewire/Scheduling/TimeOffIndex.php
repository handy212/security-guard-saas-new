<?php

namespace App\Livewire\Scheduling;

use App\Models\Guard;
use App\Models\GuardAvailability;
use App\Models\LeaveRequest;
use App\Support\TenantContext;
use Livewire\Component;

class TimeOffIndex extends Component
{
    public array $leaveForm = ['guard_id' => '', 'starts_on' => '', 'ends_on' => '', 'reason' => '', 'is_paid' => false];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
    }

    public function submitLeave(): void
    {
        $data = $this->validate([
            'leaveForm.guard_id' => 'required',
            'leaveForm.starts_on' => 'required|date',
            'leaveForm.ends_on' => 'required|date|after_or_equal:leaveForm.starts_on',
            'leaveForm.reason' => 'nullable|string',
        ])['leaveForm'];

        LeaveRequest::create($data + [
            'tenant_id' => TenantContext::id(),
            'status' => 'pending',
        ]);

        $this->leaveForm = ['guard_id' => '', 'starts_on' => '', 'ends_on' => '', 'reason' => '', 'is_paid' => false];
        session()->flash('status', 'Time off request submitted.');
    }

    public function approveLeave(int $requestId): void
    {
        LeaveRequest::findOrFail($requestId)->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);
        session()->flash('status', 'Time off approved.');
    }

    public function rejectLeave(int $requestId): void
    {
        LeaveRequest::findOrFail($requestId)->update(['status' => 'rejected']);
        session()->flash('status', 'Time off rejected.');
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        return view('livewire.scheduling.time-off-index', [
            'guards' => Guard::where('tenant_id', $tenantId)->orderBy('first_name')->get(),
            'leaveRequests' => LeaveRequest::with('assignedGuard')->where('tenant_id', $tenantId)->latest()->limit(50)->get(),
            'availabilities' => GuardAvailability::with('assignedGuard')->where('tenant_id', $tenantId)->get(),
        ])->layout('layouts.app');
    }
}
