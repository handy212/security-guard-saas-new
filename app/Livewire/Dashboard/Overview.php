<?php

namespace App\Livewire\Dashboard;

use App\Models\Incident;
use App\Models\PatrolSession;
use App\Services\DashboardMetricsService;
use App\Services\GuardLocationService;
use App\Services\NavigationBuilder;
use App\Services\PlatformBrandingService;
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

    public function render(DashboardMetricsService $metrics, TenantOnboardingService $onboarding, NavigationBuilder $nav, PlatformBrandingService $branding, GuardLocationService $locations)
    {
        abort_unless(auth()->user()?->can('dashboard.view'), 403);
        $tenantId = TenantContext::id();
        $destinations = [
            'client' => ['clients.index', 'clients.manage'],
            'site' => ['sites.index', 'sites.manage'],
            'guard' => ['guards.index', 'guards.manage'],
            'shift' => ['schedules.index', 'schedules.manage'],
            'subscription' => ['billing.subscription', 'billing.manage'],
        ];
        $steps = collect($onboarding->steps($tenantId))
            ->filter(fn (array $step): bool => $nav->routeVisible(...$destinations[$step['key']]));
        $completed = $steps->where('done', true)->count();

        $commandDashboard = $branding->current()['command_dashboard'];
        $commandMetrics = $commandDashboard ? $metrics->commandDashboard($tenantId, $nav, $locations) : [];

        return view($commandDashboard ? 'livewire.dashboard.command-center' : 'livewire.dashboard.overview', $commandMetrics + [
            'kpis' => $metrics->kpis($tenantId),
            'weekSummary' => $metrics->weekSummary($tenantId),
            'incidentTrend' => $metrics->weekTrend($tenantId, Incident::class),
            'patrolTrend' => $metrics->weekTrend($tenantId, PatrolSession::class),
            'incidentBreakdown' => $metrics->incidentBreakdown($tenantId),
            'activitySummary' => $metrics->activitySummary($tenantId),
            'todayShifts' => $metrics->todayShifts($tenantId),
            'incidentsList' => $metrics->recentIncidents($tenantId),
            'attendance' => $metrics->liveAttendance($tenantId),
            'openDispatches' => $metrics->openDispatches($tenantId),
            'onboardingSteps' => $steps,
            'onboardingProgress' => (int) round($completed / max(1, $steps->count()) * 100),
            'showOnboarding' => $completed < $steps->count(),
        ])->layout('layouts.app');
    }
}
