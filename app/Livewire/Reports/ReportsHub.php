<?php

namespace App\Livewire\Reports;

use App\Models\CustomReportSubmission;
use App\Models\DailyActivityReport;
use App\Models\ReportTemplate;
use App\Support\TenantContext;
use Livewire\Component;

class ReportsHub extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can('reports.approve'), 403);
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $daily = DailyActivityReport::where('tenant_id', $tenantId);

        return view('livewire.reports.reports-hub', [
            'stats' => [
                'daily_total' => (clone $daily)->count(),
                'daily_pending' => (clone $daily)->where('status', 'submitted')->count(),
                'daily_today' => (clone $daily)->whereDate('report_date', today())->count(),
                'templates' => ReportTemplate::where('tenant_id', $tenantId)->count(),
                'submissions' => CustomReportSubmission::where('tenant_id', $tenantId)->count(),
            ],
            'recentDaily' => DailyActivityReport::with(['site', 'assignedGuard'])
                ->where('tenant_id', $tenantId)
                ->latest()
                ->limit(6)
                ->get(),
        ])->layout('layouts.app');
    }
}
