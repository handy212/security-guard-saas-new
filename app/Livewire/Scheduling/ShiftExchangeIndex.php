<?php

namespace App\Livewire\Scheduling;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\ShiftSwapRequest;
use App\Services\EnterpriseScheduleService;
use App\Support\TenantContext;
use Livewire\Component;

class ShiftExchangeIndex extends Component
{
    use AuthorizesModuleAccess;

    public function mount(): void
    {
        $this->authorizePermission('schedules.manage');
    }

    public function approveSwap(int $swapId, EnterpriseScheduleService $service): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $service->approveSwap(ShiftSwapRequest::findOrFail($swapId), auth()->id());
        session()->flash('status', 'Shift exchange approved.');
    }

    public function rejectSwap(int $swapId): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        ShiftSwapRequest::findOrFail($swapId)->update(['status' => 'rejected']);
        session()->flash('status', 'Shift exchange rejected.');
    }

    public function render()
    {
        return view('livewire.scheduling.shift-exchange-index', [
            'swaps' => ShiftSwapRequest::with(['requestedByGuard', 'replacementGuard', 'shiftAssignment.shift.site'])
                ->where('tenant_id', TenantContext::id())
                ->latest()
                ->limit(50)
                ->get(),
        ])->layout('layouts.app');
    }
}
