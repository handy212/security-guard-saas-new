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

    public function render()
    {
        $tenantId = TenantContext::id();
        app(AnalyticsService::class)->snapshot($tenantId);

        return view('livewire.analytics.analytics-dashboard', [
            'snapshot' => AnalyticsSnapshot::where('tenant_id', $tenantId)->latest()->first(),
            'history' => AnalyticsSnapshot::where('tenant_id', $tenantId)->orderByDesc('snapshot_date')->limit(30)->get(),
        ])->layout('layouts.app');
    }
}
