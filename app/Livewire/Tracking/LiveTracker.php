<?php

namespace App\Livewire\Tracking;

use App\Models\GeofenceViolation;
use App\Models\Guard;
use App\Models\GuardIdleAlert;
use App\Models\GuardLocation;
use App\Models\Site;
use App\Models\SosAlert;
use App\Services\GuardLocationService;
use App\Support\TenantContext;
use Livewire\Attributes\Url;
use Livewire\Component;

class LiveTracker extends Component
{
    #[Url(as: 'site', except: null)]
    public ?int $siteFilter = null;

    #[Url(as: 'guard', except: null)]
    public ?int $guardFilter = null;

    #[Url(as: 'date', except: null)]
    public ?string $historyDate = null;

    #[Url(as: 'lat', except: null)]
    public ?float $focusLat = null;

    #[Url(as: 'lng', except: null)]
    public ?float $focusLng = null;

    public string $panelFilter = 'all';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('dispatch.manage'), 403);
        $this->historyDate = $this->historyDate ?: now()->toDateString();
    }

    public function applyStatFilter(string $filter): void
    {
        $this->panelFilter = match ($filter) {
            'on_duty', 'positions', 'violations', 'idle' => $filter,
            default => 'all',
        };
    }

    public function focusGuard(int $guardId): void
    {
        $this->guardFilter = $guardId;
        $this->panelFilter = 'all';

        $location = GuardLocation::query()
            ->where('tenant_id', TenantContext::id())
            ->where('guard_id', $guardId)
            ->latest('recorded_at')
            ->first();

        if ($location) {
            $this->focusLat = (float) $location->latitude;
            $this->focusLng = (float) $location->longitude;
        }
    }

    public function focusCoords(?float $lat, ?float $lng, ?int $guardId = null): void
    {
        if ($lat !== null && $lng !== null) {
            $this->focusLat = $lat;
            $this->focusLng = $lng;
        }

        if ($guardId) {
            $this->guardFilter = $guardId;
        }
    }

    public function clearFocus(): void
    {
        $this->panelFilter = 'all';
        $this->focusLat = null;
        $this->focusLng = null;
    }

    public function updatedSiteFilter($value): void
    {
        $this->siteFilter = $value === '' || $value === null ? null : (int) $value;
    }

    public function updatedGuardFilter($value): void
    {
        $this->guardFilter = $value === '' || $value === null ? null : (int) $value;
    }

    public function render(GuardLocationService $locations)
    {
        $tenantId = TenantContext::id();
        $liveGuards = $locations->onDutyGuards($tenantId);
        $guardLocations = $locations->latestForTenant($tenantId, 60);

        if ($this->siteFilter) {
            $liveGuards = $liveGuards->where('site_id', $this->siteFilter)->values();
            $siteGuardIds = $liveGuards->pluck('guard_id');
            $guardLocations = $guardLocations->whereIn('guard_id', $siteGuardIds)->values();
        }

        $historyGuards = Guard::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('id', GuardLocation::query()
                ->where('tenant_id', $tenantId)
                ->where('recorded_at', '>=', now()->subDays(7))
                ->select('guard_id'))
            ->orderBy('first_name')
            ->get();

        $onDutyByGuard = $liveGuards->keyBy('guard_id');

        $markers = $guardLocations->map(function ($loc) use ($onDutyByGuard) {
            $guard = $loc->assignedGuard;
            $duty = $onDutyByGuard->get($loc->guard_id);
            $name = $guard?->full_name ?? 'Guard';
            $siteName = $duty?->site?->name;
            $age = $loc->recorded_at?->diffForHumans() ?? '';

            return [
                'lat' => (float) $loc->latitude,
                'lng' => (float) $loc->longitude,
                'label' => $name,
                'meta' => trim(($siteName ? $siteName.' · ' : '').$age),
                'url' => $guard ? route('guards.show', $guard) : null,
                'html' => '<strong>'.e($name).'</strong>'
                    .($siteName ? '<div class="text-xs opacity-80">'.e($siteName).'</div>' : '')
                    .'<div class="text-xs opacity-80">'.e($age).'</div>'
                    .($guard ? '<a class="text-xs underline" href="'.e(route('guards.show', $guard)).'">Profile</a> · <a class="text-xs underline" href="'.e(route('dispatch.control-room')).'">Dispatch</a>' : ''),
            ];
        })->values()->all();

        $history = collect();
        if ($this->guardFilter && $this->historyDate) {
            $history = $locations->historyForGuard($this->guardFilter, $this->historyDate);
        }

        $polyline = $history->map(fn ($p) => [
            'lat' => (float) $p->latitude,
            'lng' => (float) $p->longitude,
        ])->values()->all();

        $circles = [];
        $selectedSite = $this->siteFilter
            ? Site::where('tenant_id', $tenantId)->find($this->siteFilter)
            : null;

        if ($selectedSite?->latitude && $selectedSite?->longitude && $selectedSite->geofence_radius_meters) {
            $circles[] = [
                'lat' => (float) $selectedSite->latitude,
                'lng' => (float) $selectedSite->longitude,
                'radius' => (float) $selectedSite->geofence_radius_meters,
                'color' => '#0f766e',
            ];
        }

        $defaultCenter = ['lat' => 5.6037, 'lng' => -0.1870];
        if ($this->focusLat !== null && $this->focusLng !== null) {
            $mapCenter = ['lat' => $this->focusLat, 'lng' => $this->focusLng];
        } elseif (! empty($markers[0])) {
            $mapCenter = ['lat' => $markers[0]['lat'], 'lng' => $markers[0]['lng']];
        } elseif ($selectedSite?->latitude && $selectedSite?->longitude) {
            $mapCenter = ['lat' => (float) $selectedSite->latitude, 'lng' => (float) $selectedSite->longitude];
        } else {
            $site = Site::where('tenant_id', $tenantId)->whereNotNull('latitude')->first();
            $mapCenter = $site
                ? ['lat' => (float) $site->latitude, 'lng' => (float) $site->longitude]
                : $defaultCenter;
        }

        $violations = GeofenceViolation::with(['assignedGuard', 'site'])
            ->where('tenant_id', $tenantId)
            ->when($this->siteFilter, fn ($q) => $q->where('site_id', $this->siteFilter))
            ->latest()
            ->limit(10)
            ->get();

        $idleAlerts = GuardIdleAlert::with('assignedGuard')
            ->where('tenant_id', $tenantId)
            ->whereNull('resolved_at')
            ->latest()
            ->limit(10)
            ->get();

        $sosAlerts = SosAlert::with(['assignedGuard', 'site'])
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['open', 'acknowledged'])
            ->latest()
            ->limit(10)
            ->get();

        $showGuards = in_array($this->panelFilter, ['all', 'on_duty', 'positions'], true);
        $showAlerts = in_array($this->panelFilter, ['all', 'violations', 'idle'], true);

        return view('livewire.tracking.live-tracker', [
            'liveGuards' => $liveGuards,
            'guardLocations' => $guardLocations,
            'historyGuards' => $historyGuards,
            'markers' => $markers,
            'polyline' => $polyline,
            'circles' => $circles,
            'sites' => Site::where('tenant_id', $tenantId)->orderBy('name')->get(),
            'violations' => $this->panelFilter === 'idle' ? collect() : $violations,
            'idleAlerts' => $this->panelFilter === 'violations' ? collect() : $idleAlerts,
            'sosAlerts' => $sosAlerts,
            'mapCenter' => $mapCenter,
            'showGuards' => $showGuards,
            'showAlerts' => $showAlerts,
            'fitBounds' => empty($polyline) && $this->focusLat === null,
        ])->layout('layouts.app');
    }
}
