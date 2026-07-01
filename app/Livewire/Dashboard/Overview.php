<?php

namespace App\Livewire\Dashboard;

use App\Models\Incident;
use App\Models\PatrolSession;
use App\Services\DashboardMetricsService;
use App\Services\TenantOnboardingService;
use App\Support\TenantContext;
use Livewire\Component;

class Overview extends Component
{
    public function mount(): void
    {
        if (TenantContext::isPlatformAdmin() && ! TenantContext::isViewingAsTenant()) {
            $this->redirect(route('saas.tenants'), navigate: true);

            return;
        }

        abort_unless(auth()->user()->can('dashboard.view'), 403);
    }

    public function render(DashboardMetricsService $metrics, TenantOnboardingService $onboarding)
    {
        $tenantId = TenantContext::id();

        return view('livewire.dashboard.overview', [
            'greeting' => $metrics->greeting(),
            'kpis' => $metrics->kpis($tenantId),
            'weekSummary' => $metrics->weekSummary($tenantId),
            'incidentTrend' => $metrics->weekTrend($tenantId, Incident::class),
            'patrolTrend' => $metrics->weekTrend($tenantId, PatrolSession::class),
            'todayShifts' => $metrics->todayShifts($tenantId),
            'incidentsList' => $metrics->recentIncidents($tenantId),
            'attendance' => $metrics->liveAttendance($tenantId),
            'onboardingSteps' => $onboarding->steps($tenantId),
            'onboardingProgress' => $onboarding->progress($tenantId),
            'showOnboarding' => ! $onboarding->isComplete($tenantId),
        ])->layout('layouts.app');
    }
}
