<?php

namespace App\Livewire\Dispatch;

use App\Models\AttendanceLog;
use App\Models\DispatchEvent;
use App\Models\SosAlert;
use App\Support\TenantContext;
use Livewire\Component;

class ControlRoom extends Component
{
    public function acknowledgeSos(SosAlert $alert): void
    {
        $this->authorize('acknowledge', $alert);
        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_by_user_id' => TenantContext::userId(),
            'acknowledged_at' => now(),
        ]);
    }

    public function closeEvent(DispatchEvent $event): void
    {
        abort_unless(auth()->user()->can('dispatch.manage'), 403);
        $event->update(['status' => 'closed', 'closed_at' => now()]);
    }

    public function render()
    {
        return view('livewire.dispatch.control-room', [
            'sosAlerts' => SosAlert::with(['assignedGuard', 'site'])->whereIn('status', ['open', 'acknowledged'])->latest()->get(),
            'events' => DispatchEvent::with(['site'])->latest()->limit(20)->get(),
            'liveGuards' => AttendanceLog::with(['assignedGuard', 'site'])->whereNull('clock_out_at')->latest()->get(),
        ])->layout('layouts.app');
    }
}
