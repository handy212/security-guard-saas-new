<?php

namespace App\Livewire\Dashboard;

use App\Models\{AttendanceLog, Guard, Incident, PatrolSession, Shift, Site, SosAlert};
use Livewire\Component;

class Overview extends Component
{
    public function render()
    {
        return view('livewire.dashboard.overview', [
            'stats' => [
                'active_guards' => Guard::where('status','active')->count(),
                'sites' => Site::count(),
                'today_shifts' => Shift::whereDate('starts_at', today())->count(),
                'open_incidents' => Incident::whereNotIn('status', ['closed','rejected'])->count(),
                'active_sos' => SosAlert::where('status','open')->count(),
                'patrol_completion' => PatrolSession::whereDate('started_at', today())->count() ? round(PatrolSession::whereDate('started_at', today())->where('status','completed')->count() / max(1, PatrolSession::whereDate('started_at', today())->count()) * 100) : 100,
            ],
            'incidents' => Incident::with('site')->latest()->limit(8)->get(),
            'attendance' => AttendanceLog::with(['guard','site'])->latest()->limit(8)->get(),
        ])->layout('layouts.app');
    }
}
