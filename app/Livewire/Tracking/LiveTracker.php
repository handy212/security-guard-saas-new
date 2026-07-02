<?php

namespace App\Livewire\Tracking;

use App\Models\GeofenceViolation;
use App\Models\GuardIdleAlert;
use App\Models\Site;
use App\Services\GuardLocationService;
use App\Support\TenantContext;
use Livewire\Component;

class LiveTracker extends Component
{
    public ?int $siteFilter = null;

    public ?int $guardFilter = null;

    public ?string $historyDate = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('dispatch.manage'), 403);
        $this->historyDate = now()->toDateString();
    }

    public function render(GuardLocationService $locations)
    {
        $tenantId = TenantContext::id();
        $liveGuards = $locations->onDutyGuards($tenantId);
        $guardLocations = $locations->latestForTenant($tenantId, 60);

        if ($this->siteFilter) {
            $liveGuards = $liveGuards->where('site_id', $this->siteFilter);
            $siteGuardIds = $liveGuards->pluck('guard_id');
            $guardLocations = $guardLocations->whereIn('guard_id', $siteGuardIds);
        }

        $markers = $guardLocations->map(fn ($loc) => [
            'lat' => (float) $loc->latitude,
            'lng' => (float) $loc->longitude,
            'label' => ($loc->assignedGuard?->full_name ?? 'Guard').' — '.($loc->recorded_at?->diffForHumans() ?? ''),
        ])->values()->all();

        $history = collect();
        if ($this->guardFilter && $this->historyDate) {
            $history = $locations->historyForGuard($this->guardFilter, $this->historyDate);
        }

        $polyline = $history->map(fn ($p) => ['lat' => (float) $p->latitude, 'lng' => (float) $p->longitude])->values()->all();

        $defaultCenter = ['lat' => 5.6037, 'lng' => -0.1870];
        if (! empty($markers[0])) {
            $mapCenter = $markers[0];
        } else {
            $site = Site::where('tenant_id', $tenantId)->whereNotNull('latitude')->first();
            $mapCenter = $site
                ? ['lat' => (float) $site->latitude, 'lng' => (float) $site->longitude]
                : $defaultCenter;
        }

        return view('livewire.tracking.live-tracker', [
            'liveGuards' => $liveGuards,
            'guardLocations' => $guardLocations,
            'markers' => $markers,
            'polyline' => $polyline,
            'sites' => Site::where('tenant_id', $tenantId)->orderBy('name')->get(),
            'violations' => GeofenceViolation::with(['assignedGuard', 'site'])->where('tenant_id', $tenantId)->latest()->limit(10)->get(),
            'idleAlerts' => GuardIdleAlert::with('assignedGuard')->where('tenant_id', $tenantId)->whereNull('resolved_at')->latest()->limit(10)->get(),
            'mapCenter' => $mapCenter,
        ])->layout('layouts.app');
    }
}
