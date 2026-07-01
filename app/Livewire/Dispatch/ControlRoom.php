<?php

namespace App\Livewire\Dispatch;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\AttendanceLog;
use App\Models\DispatchEvent;
use App\Models\SosAlert;
use App\Services\GuardLocationService;
use App\Support\TenantContext;
use Livewire\Component;

class ControlRoom extends Component
{
    use AuthorizesModuleAccess;

    public function mount(): void
    {
        $this->authorizePermission('dispatch.manage');
    }

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

    public function render(GuardLocationService $locations)
    {
        $tenantId = TenantContext::id();
        $sosAlerts = SosAlert::with(['assignedGuard', 'site'])->whereIn('status', ['open', 'acknowledged'])->latest()->get();
        $guardLocations = $locations->latestForTenant($tenantId);

        $markers = $sosAlerts->map(fn ($a) => [
            'lat' => (float) $a->latitude,
            'lng' => (float) $a->longitude,
            'label' => 'SOS: '.($a->assignedGuard?->full_name ?? 'Guard'),
        ])->values()->all();

        foreach ($guardLocations as $location) {
            $markers[] = [
                'lat' => (float) $location->latitude,
                'lng' => (float) $location->longitude,
                'label' => $location->assignedGuard?->full_name ?? 'Guard',
            ];
        }

        $center = $markers[0] ?? ['lat' => 0, 'lng' => 0];

        return view('livewire.dispatch.control-room', [
            'sosAlerts' => $sosAlerts,
            'events' => DispatchEvent::with(['site'])->latest()->limit(20)->get(),
            'liveGuards' => AttendanceLog::with(['assignedGuard', 'site'])->whereNull('clock_out_at')->latest()->get(),
            'markers' => $markers,
            'mapCenter' => $center,
        ])->layout('layouts.app');
    }
}
