<?php

namespace App\Livewire\Reports;

use App\Models\DailyActivityReport;
use Livewire\Component;

class DailyReportIndex extends Component
{
    public string $search='';
    public function approve(DailyActivityReport $report): void { $report->update(['status'=>'approved','approved_by_user_id'=>auth()->id(),'approved_at'=>now()]); }
    public function render(){ return view('livewire.reports.daily-report-index',['reports'=>DailyActivityReport::with(['site','guard'])->latest()->limit(50)->get()])->layout('layouts.app'); }
}
