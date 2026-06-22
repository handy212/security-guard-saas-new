<?php

namespace App\Livewire\Reports;

use App\Models\DailyActivityReport;
use App\Services\ReportService;
use Livewire\Component;

class DailyReportIndex extends Component
{
    public function approve(DailyActivityReport $report, ReportService $service): void
    {
        $this->authorize('approve', $report);
        $service->approve($report, auth()->id());
    }

    public function render()
    {
        return view('livewire.reports.daily-report-index', [
            'reports' => DailyActivityReport::with(['site', 'guard'])->latest()->limit(50)->get(),
        ])->layout('layouts.app');
    }
}
