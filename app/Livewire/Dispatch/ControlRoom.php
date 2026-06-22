<?php

namespace App\Livewire\Dispatch;

use App\Models\{AttendanceLog, DispatchEvent, SosAlert};
use Livewire\Component;

class ControlRoom extends Component
{
    public function acknowledgeSos(SosAlert $alert): void { $alert->update(['status'=>'acknowledged','acknowledged_by_user_id'=>auth()->id(),'acknowledged_at'=>now()]); }
    public function closeEvent(DispatchEvent $event): void { $event->update(['status'=>'closed','closed_at'=>now()]); }
    public function render(){ return view('livewire.dispatch.control-room',['sosAlerts'=>SosAlert::with(['guard','site'])->whereIn('status',['open','acknowledged'])->latest()->get(),'events'=>DispatchEvent::with(['site'])->latest()->limit(20)->get(),'liveGuards'=>AttendanceLog::with(['guard','site'])->whereNull('clock_out_at')->latest()->get()])->layout('layouts.app'); }
}
