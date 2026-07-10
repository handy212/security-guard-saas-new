<?php

namespace App\Livewire\Analytics;

use App\Models\AnalyticsSnapshot;
use App\Services\AnalyticsService;
use App\Support\TenantContext;
use Livewire\Component;

class AnalyticsDashboard extends Component
{
    public string $snapshotDate = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('analytics.view'), 403);
        $this->snapshotDate = today()->toDateString();
    }

    public function refreshSnapshot(AnalyticsService $analytics): void
    {
        $this->validate([
            'snapshotDate' => 'required|date',
        ]);

        $analytics->snapshot(TenantContext::id(), $this->snapshotDate);
        session()->flash('status', 'Analytics snapshot refreshed for '.$this->snapshotDate.'.');
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
