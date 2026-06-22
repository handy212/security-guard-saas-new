<?php

namespace App\Livewire\Dashboard;

use App\Models\AttendanceLog;
use App\Models\Guard;
use App\Models\Incident;
use App\Models\PatrolSession;
use App\Models\Shift;
use App\Models\Site;
use App\Models\SosAlert;
use App\Support\TenantContext;
use Livewire\Component;

class Overview extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can('dashboard.view'), 403);
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        return view('livewire.dashboard.overview', [
            'stats' => [
                'active_guards' => Guard::where('tenant_id', $tenantId)->where('status', 'active')->count(),
                'sites' => Site::where('tenant_id', $tenantId)->count(),
                'today_shifts' => Shift::where('tenant_id', $tenantId)->whereDate('starts_at', today())->count(),
                'open_incidents' => Incident::where('tenant_id', $tenantId)->whereNotIn('status', ['closed', 'rejected'])->count(),
                'active_sos' => SosAlert::where('tenant_id', $tenantId)->where('status', 'open')->count(),
                'patrol_completion' => $this->patrolCompletionRate($tenantId),
            ],
            'incidents' => Incident::with('site')->where('tenant_id', $tenantId)->latest()->limit(8)->get(),
            'attendance' => AttendanceLog::with(['assignedGuard', 'site'])->where('tenant_id', $tenantId)->latest()->limit(8)->get(),
        ])->layout('layouts.app');
    }

    private function patrolCompletionRate(int $tenantId): int
    {
        $total = PatrolSession::where('tenant_id', $tenantId)->whereDate('started_at', today())->count();

        if (! $total) {
            return 100;
        }

        $completed = PatrolSession::where('tenant_id', $tenantId)->whereDate('started_at', today())->where('status', 'completed')->count();

        return (int) round($completed / $total * 100);
    }
}
