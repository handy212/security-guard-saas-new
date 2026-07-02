<?php

namespace App\Livewire\Scheduling;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\OpenShiftBid;
use App\Services\EnterpriseScheduleService;
use App\Services\SchedulingService;
use App\Support\TenantContext;
use Livewire\Component;

class OpenShiftsIndex extends Component
{
    use AuthorizesModuleAccess;

    public function mount(): void
    {
        $this->authorizePermission('schedules.manage');
    }

    public function approveBid(int $bidId, EnterpriseScheduleService $service): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $service->approveBid(OpenShiftBid::findOrFail($bidId));
        session()->flash('status', 'Bid approved and guard assigned.');
    }

    public function render(SchedulingService $scheduling)
    {
        $tenantId = TenantContext::id();
        $openShifts = $scheduling->openShifts($tenantId, today()->toDateString());

        return view('livewire.scheduling.open-shifts-index', [
            'openShifts' => $openShifts,
            'bids' => OpenShiftBid::with(['shift.site', 'assignedGuard'])
                ->where('tenant_id', $tenantId)
                ->latest()
                ->limit(50)
                ->get(),
        ])->layout('layouts.app');
    }
}
