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

        $latest = AnalyticsSnapshot::where('tenant_id', TenantContext::id())
            ->latest('snapshot_date')
            ->value('snapshot_date');

        $this->snapshotDate = $latest
            ? \Carbon\Carbon::parse($latest)->toDateString()
            : today()->toDateString();
    }

    public function selectDate(string $date): void
    {
        $this->snapshotDate = $date;
    }

    public function goToday(): void
    {
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

        $snapshot = AnalyticsSnapshot::where('tenant_id', $tenantId)
            ->whereDate('snapshot_date', $this->snapshotDate)
            ->first();

        $history = AnalyticsSnapshot::where('tenant_id', $tenantId)
            ->orderByDesc('snapshot_date')
            ->limit(30)
            ->get();

        $chartHistory = $history->sortBy('snapshot_date')->values();

        return view('livewire.analytics.analytics-dashboard', [
            'snapshot' => $snapshot,
            'history' => $history,
            'chartHistory' => $chartHistory,
            'hasAnySnapshot' => $history->isNotEmpty(),
        ])->layout('layouts.app');
    }
}
