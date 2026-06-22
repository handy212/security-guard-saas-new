<?php

namespace App\Livewire\ClientPortal;

use App\Models\{DailyActivityReport, Incident, PatrolSession, Shift};
use Livewire\Component;

class PortalDashboard extends Component
{
    public function render(){ return view('livewire.client-portal.portal-dashboard',['shifts'=>Shift::with(['site','assignments.guard'])->latest()->limit(10)->get(),'reports'=>DailyActivityReport::with('site')->where('status','approved')->latest()->limit(10)->get(),'incidents'=>Incident::with('site')->whereIn('status',['submitted','approved','closed'])->latest()->limit(10)->get(),'patrols'=>PatrolSession::with(['route','guard'])->latest()->limit(10)->get()])->layout('layouts.app'); }
}
