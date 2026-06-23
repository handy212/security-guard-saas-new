<?php

namespace App\Livewire\Dashboard;

use App\Models\AnalyticsSnapshot;
use App\Models\AttendanceLog;
use App\Models\Guard;
use App\Models\Incident;
use App\Models\PatrolSession;
use App\Models\Shift;
use App\Models\Site;
use App\Models\SosAlert;
use App\Services\TenantOnboardingService;
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
        $onboarding = app(TenantOnboardingService::class);

        $incidentTrend = Incident::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $patrolTrend = PatrolSession::where('tenant_id', $tenantId)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $snapshot = AnalyticsSnapshot::where('tenant_id', $tenantId)->latest('snapshot_date')->first();

        return view('livewire.dashboard.overview', [
            'stats' => [
                ['label' => 'Active guards', 'value' => Guard::where('tenant_id', $tenantId)->where('status', 'active')->count(), 'tone' => 'default'],
                ['label' => 'Sites', 'value' => Site::where('tenant_id', $tenantId)->count(), 'tone' => 'info'],
                ['label' => "Today's shifts", 'value' => Shift::where('tenant_id', $tenantId)->whereDate('starts_at', today())->count(), 'tone' => 'default'],
                ['label' => 'Open incidents', 'value' => Incident::where('tenant_id', $tenantId)->whereNotIn('status', ['closed', 'rejected'])->count(), 'tone' => 'warning'],
                ['label' => 'Active SOS', 'value' => SosAlert::where('tenant_id', $tenantId)->where('status', 'open')->count(), 'tone' => 'danger'],
                ['label' => 'Patrol completion', 'value' => $this->patrolCompletionRate($tenantId).'%', 'tone' => 'success'],
            ],
            'incidents' => Incident::with('site')->where('tenant_id', $tenantId)->latest()->limit(6)->get(),
            'attendance' => AttendanceLog::with(['assignedGuard', 'site'])->where('tenant_id', $tenantId)->latest()->limit(6)->get(),
            'incidentTrend' => $incidentTrend,
            'patrolTrend' => $patrolTrend,
            'onboardingSteps' => $onboarding->steps($tenantId),
            'onboardingProgress' => $onboarding->progress($tenantId),
            'showOnboarding' => ! $onboarding->isComplete($tenantId),
            'snapshot' => $snapshot,
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
