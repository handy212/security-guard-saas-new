<?php

namespace App\Livewire\Scheduling;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\Guard;
use App\Models\OpenShiftBid;
use App\Services\EnterpriseScheduleService;
use App\Services\SchedulingService;
use App\Support\TenantContext;
use Livewire\Component;
use RuntimeException;

class OpenShiftsIndex extends Component
{
    use AuthorizesModuleAccess;

    public string $bidFilter = 'pending';

    public function mount(): void
    {
        $this->authorizePermission('schedules.manage');
    }

    public function approveBid(int $bidId, EnterpriseScheduleService $service): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);

        try {
            $service->approveBid(OpenShiftBid::where('tenant_id', TenantContext::id())->findOrFail($bidId));
        } catch (RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        session()->flash('status', 'Bid approved and guard assigned.');
    }

    public function rejectBid(int $bidId, EnterpriseScheduleService $service): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $service->rejectBid(OpenShiftBid::where('tenant_id', TenantContext::id())->findOrFail($bidId));
        session()->flash('status', 'Bid rejected.');
    }

    public function render(SchedulingService $scheduling)
    {
        $tenantId = TenantContext::id();

        return view('livewire.scheduling.open-shifts-index', [
            'openShifts' => $scheduling->openShifts($tenantId, today()->toDateString()),
            'bids' => OpenShiftBid::with(['shift.site', 'assignedGuard'])
                ->where('tenant_id', $tenantId)
                ->when($this->bidFilter !== 'all', fn ($q) => $q->where('status', $this->bidFilter))
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->latest()
                ->limit(50)
                ->get(),
            'pendingBidCount' => OpenShiftBid::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'guards' => Guard::where('tenant_id', $tenantId)->where('status', 'active')->orderBy('first_name')->get(),
        ])->layout('layouts.app');
    }
}
