<?php

namespace App\Livewire\Schedules;

use App\Models\OpenShiftBid;
use App\Models\ShiftSwapRequest;
use App\Services\EnterpriseScheduleService;
use Livewire\Component;

class ShiftMarketplace extends Component
{
    public function approveSwap(ShiftSwapRequest $swap, EnterpriseScheduleService $service): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $service->approveSwap($swap, auth()->id());
    }

    public function approveBid(OpenShiftBid $bid, EnterpriseScheduleService $service): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $service->approveBid($bid);
    }

    public function render()
    {
        return view('livewire.schedules.shift-marketplace', [
            'bids' => OpenShiftBid::with(['shift', 'assignedGuard'])->latest()->limit(50)->get(),
            'swaps' => ShiftSwapRequest::with(['requestedByGuard', 'replacementGuard'])->latest()->limit(50)->get(),
        ])->layout('layouts.app');
    }
}
