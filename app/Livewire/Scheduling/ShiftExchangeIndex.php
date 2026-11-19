<?php

namespace App\Livewire\Scheduling;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\EmbedsInScheduler;
use App\Models\ShiftSwapRequest;
use App\Services\EnterpriseScheduleService;
use App\Support\TenantContext;
use Livewire\Component;

class ShiftExchangeIndex extends Component
{
    use AuthorizesModuleAccess;
    use EmbedsInScheduler;

    public string $swapFilter = 'pending';

    public function mount(): void
    {
        $this->authorizePermission('schedules.manage');
    }

    public function approveSwap(int $swapId, EnterpriseScheduleService $service): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        try {
            $service->approveSwap(ShiftSwapRequest::where('tenant_id', TenantContext::id())->findOrFail($swapId), auth()->id());
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }
        session()->flash('status', 'Shift exchange approved.');
    }

    public function rejectSwap(int $swapId, EnterpriseScheduleService $service): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        try {
            $service->rejectSwap(ShiftSwapRequest::where('tenant_id', TenantContext::id())->findOrFail($swapId));
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }
        session()->flash('status', 'Shift exchange rejected.');
    }

    public function render()
    {
        return view('livewire.scheduling.shift-exchange-index', [
            'swaps' => ShiftSwapRequest::with(['requestedByGuard', 'replacementGuard', 'shiftAssignment.shift.site'])
                ->where('tenant_id', TenantContext::id())
                ->when($this->swapFilter !== 'all', fn ($q) => $q->where('status', $this->swapFilter))
                ->when($this->swapFilter === 'pending', fn ($q) => $q->whereHas('shiftAssignment.shift', fn ($shift) => $shift->where('starts_at', '>', now())))
                ->latest()
                ->limit(50)
                ->get(),
            'pendingSwapCount' => ShiftSwapRequest::where('tenant_id', TenantContext::id())
                ->where('status', 'pending')
                ->whereHas('shiftAssignment.shift', fn ($shift) => $shift->where('starts_at', '>', now()))
                ->count(),
        ])->layout('layouts.app');
    }
}
