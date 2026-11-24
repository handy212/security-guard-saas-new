<?php

namespace App\Livewire\Tracking;

use App\Enums\DispatchStatus;
use App\Models\AttendanceLog;
use App\Models\Branch;
use App\Models\DispatchEvent;
use App\Models\GeofenceViolation;
use App\Models\Guard;
use App\Models\GuardIdleAlert;
use App\Models\GuardLocation;
use App\Models\Incident;
use App\Models\PatriotAccountMapping;
use App\Models\ShiftAssignment;
use App\Models\Site;
use App\Models\SosAlert;
use App\Services\GuardLocationService;
use App\Services\SiteProtectionService;
use App\Support\TenantContext;
use Illuminate\Support\Collection;
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

    #[Url(as: 'dispatch', except: null)]
    public ?int $dispatchFocusId = null;

    public string $panelFilter = 'all';

    public string $coverageFilter = 'all';

    public string $deploymentFilter = 'all';

    public string $branchFilter = '';

    public string $search = '';

    public bool $showGuardMarkers = true;

    public ?int $selectedSiteId = null;

    public ?int $selectedAssignmentId = null;

    private const COVERAGE_STATES = [
        'protected' => 'Protected',
        'unprotected' => 'Unprotected',
        'understaffed' => 'Understaffed',
        'verification_required' => 'Verification required',
        'not_scheduled' => 'Not scheduled',
        'inactive' => 'Inactive',
    ];

    private const DEPLOYMENT_STATES = [
        'active' => 'On duty',
        'deployed' => 'Deployed',
        'awaiting_check_in' => 'Awaiting check-in',
        'stale_gps' => 'Stale or missing GPS',
        'outside_geofence' => 'Outside geofence',
        'verification_required' => 'Verification required',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('dispatch.manage'), 403);
        $this->historyDate = $this->historyDate ?: now()->toDateString();
        if ($siteId = request()->integer('site')) {
            $this->openSite($siteId);
        }
    }

    public function selectCoverage(string $status): void
    {
        $this->deploymentFilter = 'all';
        $this->coverageFilter = array_key_exists($status, self::COVERAGE_STATES) ? $status : 'all';
    }

    public function selectDeploymentFilter(string $status): void
    {
        $this->coverageFilter = 'all';
        $this->deploymentFilter = array_key_exists($status, self::DEPLOYMENT_STATES) ? $status : 'all';
    }

    public function openSite(int $siteId): void
    {
        $site = Site::forTenant(TenantContext::id())->findOrFail($siteId);
        $this->selectedSiteId = $site->id;
        $this->selectedAssignmentId = null;
        $this->focusCoords($site->latitude, $site->longitude);
    }

    public function openDeployment(int $assignmentId): void
    {
        $assignment = ShiftAssignment::forTenant(TenantContext::id())->with('shift')->findOrFail($assignmentId);
        abort_unless($assignment->shift?->tenant_id === TenantContext::id(), 404);
        $this->openSite($assignment->shift->site_id);
        $this->selectedAssignmentId = $assignment->id;
    }

    public function resetMonitoringFilters(): void
    {
        $this->reset('coverageFilter', 'deploymentFilter', 'branchFilter', 'search', 'siteFilter', 'selectedSiteId', 'selectedAssignmentId');
        $this->clearFocus();
    }

    public function focusGuard(int $guardId): void
    {
        $this->guardFilter = $guardId;
        $this->dispatchFocusId = null;
        $this->panelFilter = 'all';

        $location = GuardLocation::query()
            ->where('tenant_id', TenantContext::id())
            ->where('guard_id', $guardId)
            ->latest('recorded_at')
            ->first();

        $this->focusCoords($location ? (float) $location->latitude : null, $location ? (float) $location->longitude : null);
    }

    public function focusCoords(?float $lat, ?float $lng, ?int $guardId = null): void
    {
        $this->dispatchFocusId = null;
        $this->focusLat = $lat !== null && $lng !== null ? $lat : null;
        $this->focusLng = $this->focusLat !== null ? $lng : null;

        if ($guardId) {
            $this->guardFilter = $guardId;
        }
    }

    public function clearFocus(): void
    {
        $this->panelFilter = 'all';
        $this->focusLat = null;
        $this->focusLng = null;
        $this->dispatchFocusId = null;
    }

    public function updatedSiteFilter($value): void
    {
        $this->siteFilter = $value === '' || $value === null ? null : (int) $value;
        $this->reset('selectedSiteId', 'selectedAssignmentId', 'focusLat', 'focusLng', 'dispatchFocusId');
    }

    public function updatedGuardFilter($value): void
    {
        $this->guardFilter = $value === '' || $value === null ? null : (int) $value;
    }

    public function render(GuardLocationService $locations, SiteProtectionService $protection)
    {
        $tenantId = TenantContext::id();
        $snapshot = $protection->snapshot($tenantId);
        $coverage = $snapshot['sites'];
        $deployments = $snapshot['deployments'];
        $accounts = PatriotAccountMapping::forTenant($tenantId)->whereIn('site_id', $coverage->keys())
            ->get(['site_id', 'account_number', 'account_reference'])->groupBy('site_id');
        $matchingSites = $coverage->map(function (array $row) use ($accounts): array {
            $row['siteMatchesSearch'] = $this->siteMatchesSearch($row['site'], $accounts->get($row['site']->id, collect()));
            $row['matchingDeployments'] = $row['deployments']->filter(fn (array $item): bool => $this->matchesDeployment($item, $row['siteMatchesSearch']));

            return $row;
        })->filter(fn (array $row): bool => $this->matchesSite($row));
        $matchingDeployments = $matchingSites->pluck('matchingDeployments')->collapse();
        $selectedCoverage = $matchingSites->get($this->selectedSiteId ?? $this->siteFilter);
        $selectedDeployment = $selectedCoverage
            ? $selectedCoverage['deployments']->first(fn (array $item): bool => $item['assignment']->id === $this->selectedAssignmentId)
            : null;
        $currentDispatch = $selectedDeployment && $selectedDeployment['guard']
            ? DispatchEvent::forTenant($tenantId)->where('guard_id', $selectedDeployment['guard']->id)
                ->whereIn('status', [DispatchStatus::ASSIGNED, DispatchStatus::EN_ROUTE, DispatchStatus::ON_SCENE])
                ->latest()->first()
            : null;

        $liveGuards = $locations->onDutyGuards($tenantId)->whereNotNull('clock_in_at')->unique('guard_id')->values();
        if ($this->filtersAffectMap()) {
            $visibleGuards = $matchingDeployments->pluck('assignment.guard_id')->merge(
                $this->deploymentFilter === 'all'
                    ? $liveGuards->whereIn('site_id', $matchingSites->where('siteMatchesSearch', true)->keys())
                        ->when($this->branchFilter !== '', fn (Collection $logs) => $logs->filter(fn (AttendanceLog $log): bool => (int) $log->site?->dispatch_region_id === (int) $this->branchFilter
                            || (int) $log->assignedGuard?->branch_id === (int) $this->branchFilter))
                        ->pluck('guard_id')
                    : [],
            )->unique();
            $liveGuards = $liveGuards->whereIn('guard_id', $visibleGuards)->values();
            $guardLocations = $locations->positionsForGuards($tenantId, $visibleGuards->values()->all());
        } else {
            $guardLocations = $locations->latestForTenant($tenantId, 60)->keyBy('guard_id');
        }
        foreach ($matchingDeployments as $deployment) {
            if ($deployment['onDuty'] && $deployment['location']) {
                $guardLocations->put($deployment['guard']->id, $deployment['location']);
            }
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
        $guardNames = Guard::forTenant($tenantId)->whereIn('id', $guardLocations->keys())
            ->get(['id', 'first_name', 'last_name'])->keyBy('id');

        $guardMarkers = $guardLocations->map(function (GuardLocation $loc) use ($onDutyByGuard, $guardNames): array {
            $guard = $guardNames->get($loc->guard_id);
            $duty = $onDutyByGuard->get($loc->guard_id);
            $name = $guard?->full_name ?? 'Guard';
            $siteName = $duty?->site?->name;
            $age = ($loc->recorded_at->lt(now()->subMinutes(SiteProtectionService::FRESH_GPS_MINUTES)) ? 'Last known · ' : '')
                .$loc->recorded_at->diffForHumans();
            $profile = $guard && auth()->user()->can('guards.manage') ? route('guards.show', $guard) : null;

            return [
                'lat' => (float) $loc->latitude,
                'lng' => (float) $loc->longitude,
                'label' => $name,
                'meta' => trim(($siteName ? $siteName.' · ' : '').$age),
                'url' => $profile,
            ];
        })->values()->all();

        $siteMarkers = $matchingSites->filter(fn (array $row): bool => $row['site']->latitude !== null && $row['site']->longitude !== null)
            ->map(fn (array $row): array => [
                'lat' => $row['site']->latitude, 'lng' => $row['site']->longitude, 'label' => $row['site']->name,
                'siteId' => $row['site']->id, 'status' => $row['status'], 'statusLabel' => self::COVERAGE_STATES[$row['status']],
            ])->values()->all();
        $markers = array_merge($siteMarkers, $this->showGuardMarkers ? $guardMarkers : []);

        $patriotAlarms = DispatchEvent::forTenant($tenantId)->with('site')
            ->whereNotNull('patriot_job_id')
            ->whereIn('status', [DispatchStatus::OPEN, DispatchStatus::ASSIGNED, DispatchStatus::EN_ROUTE, DispatchStatus::ON_SCENE])
            ->latest('id')->get();
        $alarmMarkers = $patriotAlarms->map(function (DispatchEvent $alarm): ?array {
            $location = $alarm->patriot_payload['location'] ?? [];
            $lat = $alarm->latitude ?? $location['latitude'] ?? $alarm->site?->latitude;
            $lng = $alarm->longitude ?? $location['longitude'] ?? $alarm->site?->longitude;
            if (! is_numeric($lat) || ! is_numeric($lng) || abs((float) $lat) > 90 || abs((float) $lng) > 180) {
                return null;
            }

            return [
                'lat' => (float) $lat, 'lng' => (float) $lng, 'alarmId' => $alarm->id,
                'label' => $alarm->dispatch_number ?? 'Patriot alarm',
                'critical' => $alarm->priority->value === 'critical',
                'meta' => $alarm->description ?? 'Patriot alarm',
                'url' => route('dispatch.control-room', ['q' => $alarm->dispatch_number]),
            ];
        })->filter()->values();
        $markers = array_merge($alarmMarkers->all(), $markers);

        $history = collect();
        if ($this->guardFilter && $this->historyDate) {
            $history = $locations->historyForGuard($this->guardFilter, $this->historyDate, $tenantId);
        }

        $polyline = $history->map(fn ($p) => [
            'lat' => (float) $p->latitude,
            'lng' => (float) $p->longitude,
        ])->values()->all();

        $circles = [];
        $selectedSite = $selectedCoverage['site'] ?? null;

        if ($selectedSite?->latitude !== null && $selectedSite->longitude !== null && $selectedSite->geofence_radius_meters) {
            $circles[] = [
                'lat' => (float) $selectedSite->latitude,
                'lng' => (float) $selectedSite->longitude,
                'radius' => (float) $selectedSite->geofence_radius_meters,
                'color' => '#0f766e',
            ];
        }

        $defaultCenter = ['lat' => 5.6037, 'lng' => -0.1870];
        $hasFocus = $this->focusLat !== null && $this->focusLng !== null
            && abs($this->focusLat) <= 90 && abs($this->focusLng) <= 180;
        if ($hasFocus) {
            $mapCenter = ['lat' => $this->focusLat, 'lng' => $this->focusLng];
            if ($this->dispatchFocusId) {
                $dispatch = DispatchEvent::forTenant($tenantId)->find($this->dispatchFocusId);
                if ($dispatch && ! $alarmMarkers->contains('alarmId', $dispatch->id)) {
                    $markers[] = $mapCenter + ['label' => $dispatch->dispatch_number ?? 'Dispatch location'];
                }
            }
        } elseif (! empty($markers[0])) {
            $mapCenter = ['lat' => $markers[0]['lat'], 'lng' => $markers[0]['lng']];
        } elseif ($selectedSite?->latitude !== null && $selectedSite->longitude !== null) {
            $mapCenter = ['lat' => (float) $selectedSite->latitude, 'lng' => (float) $selectedSite->longitude];
        } else {
            $site = $coverage->pluck('site')->first(fn (Site $site): bool => $site->latitude !== null && $site->longitude !== null);
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

        $latestIdleAlerts = GuardIdleAlert::forTenant($tenantId)->whereNull('resolved_at')
            ->select('id')
            ->selectRaw('ROW_NUMBER() OVER (PARTITION BY guard_id ORDER BY alerted_at DESC, id DESC) AS alert_rank');

        $idleAlerts = GuardIdleAlert::forTenant($tenantId)->with('assignedGuard')
            ->whereIn('id', fn ($query) => $query->fromSub($latestIdleAlerts, 'idle_alerts')->select('id')->where('alert_rank', 1))
            ->latest('alerted_at')->latest('id')->limit(10)->get();

        $sosAlerts = SosAlert::with(['assignedGuard', 'site'])
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['open', 'acknowledged'])
            ->latest()
            ->limit(10)
            ->get();

        $showGuards = in_array($this->panelFilter, ['all', 'on_duty', 'positions'], true);
        $showAlerts = in_array($this->panelFilter, ['all', 'violations', 'idle'], true);

        return view('livewire.tracking.live-tracker', [
            'coverageStates' => self::COVERAGE_STATES,
            'hasMonitoringFilters' => $this->filtersAffectMap(),
            'deploymentStates' => self::DEPLOYMENT_STATES,
            'coverageSummary' => $this->coverageSummary($coverage, $deployments),
            'matchingSites' => $matchingSites,
            'siteMarkerCount' => count($siteMarkers),
            'matchingDeployments' => $matchingDeployments,
            'selectedCoverage' => $selectedCoverage,
            'selectedDeployment' => $selectedDeployment,
            'currentDispatch' => $currentDispatch,
            'selectedAccounts' => $accounts->get($selectedCoverage['site']->id ?? null, collect()),
            'recentAttendance' => $selectedCoverage
                ? AttendanceLog::forTenant($tenantId)->where('site_id', $selectedCoverage['site']->id)->with('assignedGuard')
                    ->latest('recorded_at')->limit(5)->get()
                : collect(),
            'relatedIncidents' => $selectedCoverage && auth()->user()->can('incidents.manage')
                ? Incident::forTenant($tenantId)->where('site_id', $selectedCoverage['site']->id)->latest('reported_at')->limit(5)->get()
                : collect(),
            'branches' => Branch::forTenant($tenantId)->orderBy('name')->get(['id', 'name']),
            'liveGuards' => $liveGuards,
            'historyGuards' => $historyGuards,
            'markers' => $markers,
            'polyline' => $polyline,
            'circles' => $circles,
            'sites' => $coverage->pluck('site'),
            'violations' => $this->panelFilter === 'idle' ? collect() : $violations,
            'idleAlerts' => $this->panelFilter === 'violations' ? collect() : $idleAlerts,
            'sosAlerts' => $sosAlerts,
            'mapCenter' => $mapCenter,
            'showGuards' => $showGuards,
            'showAlerts' => $showAlerts,
            'fitBounds' => empty($polyline) && ! $hasFocus,
            'viewportKey' => json_encode([$this->focusLat, $this->focusLng, $this->siteFilter, $this->coverageFilter,
                $this->deploymentFilter, $this->branchFilter, $this->search, $this->guardFilter, $this->historyDate, $alarmMarkers->pluck('alarmId')->all()]),
        ])->layout('layouts.app');
    }

    private function filtersAffectMap(): bool
    {
        return $this->siteFilter || $this->coverageFilter !== 'all' || $this->deploymentFilter !== 'all'
            || $this->branchFilter !== '' || trim($this->search) !== '';
    }

    private function matchesSite(array $row): bool
    {
        $site = $row['site'];
        $needsMatchingGuard = $this->deploymentFilter !== 'all' || ! $row['siteMatchesSearch']
            || ($this->branchFilter !== '' && (int) $site->dispatch_region_id !== (int) $this->branchFilter);

        return (! $this->siteFilter || $site->id === $this->siteFilter)
            && ($this->coverageFilter === 'all' || $row['status'] === $this->coverageFilter)
            && (! $needsMatchingGuard || $row['matchingDeployments']->isNotEmpty());
    }

    private function siteMatchesSearch(Site $site, Collection $accounts): bool
    {
        return $this->matchesSearch([
            $site->name, $site->clientAccount?->name, $site->code, $site->patriot_client_ref,
            ...$accounts->flatMap(fn (PatriotAccountMapping $account): array => [$account->account_number, $account->account_reference]),
        ]);
    }

    private function matchesDeployment(array $deployment, bool $siteMatchesSearch): bool
    {
        $guard = $deployment['guard'];
        if ($this->branchFilter !== '' && (int) $deployment['site']->dispatch_region_id !== (int) $this->branchFilter
            && (int) $guard?->branch_id !== (int) $this->branchFilter) {
            return false;
        }
        if (! $siteMatchesSearch && ! $this->matchesSearch([$guard?->full_name, $guard?->employee_number])) {
            return false;
        }

        return match ($this->deploymentFilter) {
            'all' => true,
            'active' => $deployment['onDuty'],
            'stale_gps' => $deployment['onDuty'] && $deployment['stale'],
            default => $deployment['status'] === $this->deploymentFilter,
        };
    }

    /** @param list<string|null> $values */
    private function matchesSearch(array $values): bool
    {
        $term = mb_strtolower(trim($this->search));

        return $term === '' || str_contains(mb_strtolower(implode(' ', $values)), $term);
    }

    /** @return array<string, int> */
    private function coverageSummary(Collection $coverage, Collection $deployments): array
    {
        $onDuty = $deployments->where('onDuty', true)->unique('assignment.guard_id');

        return [
            'monitored' => $coverage->where('status', '!=', 'inactive')->count(),
            'protected' => $coverage->where('status', 'protected')->count(),
            'unprotected' => $coverage->where('status', 'unprotected')->count(),
            'understaffed' => $coverage->where('status', 'understaffed')->count(),
            'verification_required' => $coverage->where('status', 'verification_required')->count(),
            'not_scheduled' => $coverage->where('status', 'not_scheduled')->count(),
            'deployed' => $onDuty->count(),
            'stale_gps' => $onDuty->where('stale', true)->count(),
        ];
    }
}
