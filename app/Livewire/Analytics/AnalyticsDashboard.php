<?php

namespace App\Livewire\Analytics;

use Livewire\Component;

class AnalyticsDashboard extends Component
{
    public function render()
    {
        return view('livewire.analytics.analytics-dashboard', ['snapshot'=>\App\Models\AnalyticsSnapshot::latest()->first(),'history'=>\App\Models\AnalyticsSnapshot::orderByDesc('snapshot_date')->limit(30)->get()])->layout('layouts.app');
    }
}
