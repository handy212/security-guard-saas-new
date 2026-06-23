<?php

namespace App\Livewire\ClientPortal;

use App\Models\DailyActivityReport;
use App\Models\Incident;
use App\Models\PatrolSession;
use App\Models\Shift;
use App\Support\TenantContext;
use Livewire\Component;

class PortalDashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can('client_portal.view'), 403);
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $clientId = auth()->user()->client_account_id;

        $shiftQuery = Shift::with(['site', 'assignments.assignedGuard'])->where('tenant_id', $tenantId);
        $reportQuery = DailyActivityReport::with('site')->where('tenant_id', $tenantId)->where('status', 'approved');
        $incidentQuery = Incident::with('site')->where('tenant_id', $tenantId)->whereIn('status', ['submitted', 'approved', 'closed']);
        $patrolQuery = PatrolSession::with(['route', 'assignedGuard'])->where('tenant_id', $tenantId);

        if ($clientId) {
            $shiftQuery->where('client_account_id', $clientId);
            $reportQuery->whereHas('site', fn ($q) => $q->where('client_account_id', $clientId));
            $incidentQuery->whereHas('site', fn ($q) => $q->where('client_account_id', $clientId));
            $patrolQuery->whereHas('route.site', fn ($q) => $q->where('client_account_id', $clientId));
        }

        return view('livewire.client-portal.portal-dashboard', [
            'shifts' => $shiftQuery->latest()->limit(10)->get(),
            'reports' => $reportQuery->latest()->limit(10)->get(),
            'incidents' => $incidentQuery->latest()->limit(10)->get(),
            'patrols' => $patrolQuery->latest()->limit(10)->get(),
        ])->layout('layouts.portal');
    }
}
