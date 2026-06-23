<?php

namespace App\Livewire\Analytics;

use App\Models\AnalyticsSnapshot;
use App\Services\AnalyticsService;
use App\Support\TenantContext;
use Livewire\Component;

class AnalyticsDashboard extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can('analytics.view'), 403);
    }

    public function refreshSnapshot(AnalyticsService $analytics): void
    {
        $analytics->snapshot(TenantContext::id());
        session()->flash('status', 'Analytics snapshot refreshed.');
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        return view('livewire.analytics.analytics-dashboard', [
            'snapshot' => AnalyticsSnapshot::where('tenant_id', $tenantId)->latest('snapshot_date')->first(),
            'history' => AnalyticsSnapshot::where('tenant_id', $tenantId)->orderByDesc('snapshot_date')->limit(30)->get(),
        ])->layout('layouts.app');
    }
}
