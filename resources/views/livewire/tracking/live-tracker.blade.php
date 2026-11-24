<div wire:poll.15s x-data="{
    fullscreen: false,
    expanded: false,
    sitesCollapsed: false,
    toggleSites() {
        this.sitesCollapsed = !this.sitesCollapsed;
        this.resizeMap();
    },
    resizeMap() {
        this.$nextTick(() => requestAnimationFrame(() => window.dispatchEvent(new Event('resize'))));
    },
    async toggleFullscreen() {
        if (this.expanded) {
            this.expanded = false;
            this.resizeMap();
            return;
        }
        if (document.fullscreenElement === this.$refs.mapWorkspace) {
            await document.exitFullscreen();
            return;
        }
        try {
            await this.$refs.mapWorkspace.requestFullscreen();
        } catch {
            this.expanded = true;
            this.resizeMap();
        }
    },
    async showSite(siteId) {
        await this.$wire.openSite(siteId);
        if ((this.fullscreen || this.expanded) && this.sitesCollapsed) this.toggleSites();
        if (matchMedia('(max-width: 1279px)').matches) {
            this.$nextTick(() => {
                const details = this.$refs.siteDetails.querySelector('[data-selected-site]');
                details?.focus({ preventScroll: true });
                details?.scrollIntoView({ block: 'start' });
            });
        }
    }
}" x-on:tracking-site-selected.window="showSite($event.detail.siteId)"
    x-on:fullscreenchange.document="fullscreen = document.fullscreenElement === $refs.mapWorkspace; resizeMap()"
    x-on:keydown.escape.window="if (expanded) toggleFullscreen()">
    <x-page-shell title="Live Tracker" compact>
        <x-slot:actions>
            <x-button size="sm" variant="secondary" :href="route('dispatch.control-room')">Open dispatch</x-button>
        </x-slot:actions>

        <div class="tracking-summary grid grid-cols-2 gap-1 md:grid-cols-3 xl:grid-cols-6" aria-label="Site protection and deployment totals">
            <x-stat-card compact label="Monitored sites" :value="$coverageSummary['monitored']" icon="sites" wire:click="resetMonitoringFilters" />
            <x-stat-card compact label="Protected" :value="$coverageSummary['protected']" icon="guards" tone="success" wire:click="selectCoverage('protected')" :active="$coverageFilter === 'protected'" />
            <x-stat-card compact label="Unprotected" :value="$coverageSummary['unprotected']" icon="incidents" tone="danger" wire:click="selectCoverage('unprotected')" :active="$coverageFilter === 'unprotected'" />
            <x-stat-card compact label="Understaffed" :value="$coverageSummary['understaffed']" icon="users" tone="warning" wire:click="selectCoverage('understaffed')" :active="$coverageFilter === 'understaffed'" />
            <x-stat-card compact label="Guards on duty" :value="$coverageSummary['deployed']" icon="guards" tone="info" wire:click="selectDeploymentFilter('active')" :active="$deploymentFilter === 'active'" />
            <x-stat-card compact label="Stale or missing GPS" :value="$coverageSummary['stale_gps']" icon="gps" tone="warning" wire:click="selectDeploymentFilter('stale_gps')" :active="$deploymentFilter === 'stale_gps'" />
        </div>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-600 dark:text-zinc-300">
            <button type="button" class="page-link" wire:click="selectCoverage('verification_required')">{{ $coverageSummary['verification_required'] }} awaiting verification</button>
            <button type="button" class="page-link" wire:click="selectCoverage('not_scheduled')">{{ $coverageSummary['not_scheduled'] }} without coverage scheduled</button>
        </div>

        @if ($sosAlerts->isNotEmpty())
            <section class="card-surface overflow-hidden border-red-200/90 dark:border-red-900/50">
                <div class="card-header border-red-100 bg-red-50/80 dark:border-red-900/40 dark:bg-red-950/40">
                    <div class="flex items-center gap-2">
                        <span class="relative flex h-2.5 w-2.5">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
                        </span>
                        <div>
                            <h2 class="card-header-title text-red-900 dark:text-red-100">Open SOS</h2>
                            <p class="card-header-meta text-red-700/90 dark:text-red-300">{{ $sosAlerts->count() }} alert{{ $sosAlerts->count() === 1 ? '' : 's' }} · respond from dispatch</p>
                        </div>
                    </div>
                    <a href="{{ route('dispatch.control-room', ['status' => 'active', 'priority' => 'critical']) }}" class="page-link shrink-0 text-red-700 dark:text-red-300">Open dispatch</a>
                </div>
                <div class="divide-y divide-red-100 dark:divide-red-900/40">
                    @foreach ($sosAlerts as $alert)
                        <div class="flex flex-wrap items-center justify-between gap-3 bg-red-50/40 px-4 py-3 dark:bg-red-950/20" wire:key="track-sos-{{ $alert->id }}">
                            <div class="min-w-0 text-sm">
                                <span class="font-semibold text-red-900 dark:text-red-100">{{ $alert->assignedGuard?->full_name ?? 'Guard' }}</span>
                                <span class="text-red-700 dark:text-red-300"> · {{ $alert->site?->name ?? 'Unknown site' }}</span>
                                <span class="text-xs text-red-600 dark:text-red-400"> · {{ $alert->raised_at?->diffForHumans() }}</span>
                            </div>
                            <div class="flex shrink-0 gap-2">
                                @if ($alert->latitude && $alert->longitude)
                                    <x-button size="sm" variant="secondary" wire:click="focusCoords({{ $alert->latitude }}, {{ $alert->longitude }}, {{ $alert->guard_id ?? 'null' }})">View on map</x-button>
                                @endif
                                <x-button size="sm" variant="danger" :href="route('dispatch.control-room')">Respond</x-button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <div class="tracking-filters panel-surface grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-[minmax(12rem,2fr)_repeat(3,minmax(0,1fr))_auto] lg:items-end">
            <div class="sm:col-span-2 lg:col-span-1">
                <label for="tracking-search" class="mb-1 block text-xs font-medium">Search</label>
                <x-search-input id="tracking-search" wire:model.live.debounce.300ms="search"
                    placeholder="Site, customer, guard, or ID" />
            </div>
            <x-select wire:model.live="coverageFilter" label="Site protection">
                <option value="all">All sites</option>
                @foreach ($coverageStates as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-select>
            <x-select wire:model.live="deploymentFilter" label="Deployment">
                <option value="all">All deployments</option>
                @foreach ($deploymentStates as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-select>
            <x-select wire:model.live="branchFilter" label="Region">
                <option value="">All regions</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </x-select>
            <x-button type="button" variant="secondary" wire:click="resetMonitoringFilters"
                :disabled="! $hasMonitoringFilters" class="justify-self-start whitespace-nowrap">Clear filters</x-button>
        </div>

        <div wire:key="tracking-map-workspace" x-ref="mapWorkspace" x-bind:data-expanded="expanded"
            x-bind:data-sites-collapsed="(fullscreen || expanded) && sitesCollapsed"
            x-trap.noscroll.inert="expanded" class="tracking-map-workspace grid items-start gap-3 xl:grid-cols-[minmax(0,2fr)_minmax(19rem,1fr)]">
            <div class="min-w-0 space-y-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="text-sm text-zinc-600 dark:text-zinc-300">
                        {{ $siteMarkerCount }} {{ Str::plural('site', $siteMarkerCount) }} on the map
                        @if ($matchingSites->count() > $siteMarkerCount)
                            · {{ $matchingSites->count() - $siteMarkerCount }} without GPS coordinates
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model.live="showGuardMarkers" class="rounded border-zinc-300 text-accent-700">
                            Show guard positions
                        </label>
                        <x-button type="button" size="sm" variant="secondary" x-show="fullscreen || expanded" x-cloak
                            x-on:click="toggleSites()" aria-controls="tracking-sites-panel"
                            x-bind:aria-expanded="!sitesCollapsed" x-text="sitesCollapsed ? 'Show sites' : 'Hide sites'">Hide sites</x-button>
                        <x-button type="button" size="sm" variant="secondary" x-on:click="toggleFullscreen()"
                            x-bind:aria-pressed="fullscreen || expanded" x-text="fullscreen || expanded ? 'Exit fullscreen' : 'Fullscreen'">Fullscreen</x-button>
                    </div>
                </div>

                <x-map id="tracking-map" :lat="$mapCenter['lat']" :lng="$mapCenter['lng']"
                    :markers="$markers" :polyline="$polyline" :circles="$circles" :fit-bounds="$fitBounds"
                    :viewport-key="$viewportKey" height="clamp(360px, 58vh, 720px)" />

                <div class="flex flex-wrap gap-x-4 gap-y-2 py-1 text-xs" aria-label="Site marker legend">
                    @foreach (['protected' => 'Protected', 'unprotected' => 'Unprotected', 'understaffed' => 'Understaffed', 'verification_required' => 'Verification required', 'not_scheduled' => 'Inactive or not scheduled'] as $status => $label)
                        <span class="inline-flex items-center gap-1.5"><span class="tracking-shield tracking-shield--{{ $status }}" aria-hidden="true">{{ match ($status) { 'protected' => '✓', 'unprotected' => '×', 'not_scheduled' => '–', default => '!' } }}</span>{{ $label }}</span>
                    @endforeach
                </div>
            </div>

            <div id="tracking-sites-panel" class="tracking-map-details min-w-0 space-y-3" x-show="!((fullscreen || expanded) && sitesCollapsed)">
                <div x-ref="siteDetails" class="empty:hidden">
                    @include('livewire.tracking.site-details')
                </div>

                <x-section-card compact title="Sites" flush>
                    <div class="max-h-72 overflow-y-auto">
                        @forelse ($matchingSites as $row)
                            @php
                                $site = $row['site'];
                            @endphp
                            <button type="button" wire:key="coverage-site-{{ $site->id }}" x-on:click="showSite({{ $site->id }})"
                                aria-pressed="{{ $selectedCoverage && $selectedCoverage['site']->id === $site->id ? 'true' : 'false' }}"
                                class="list-row w-full gap-2 text-left hover:bg-zinc-50 aria-pressed:bg-accent-50 dark:hover:bg-zinc-800 dark:aria-pressed:bg-accent-950">
                                <span class="min-w-0 flex-1">
                                    <strong class="block truncate">{{ $site->name }}</strong>
                                    @if ($site->clientAccount && $site->clientAccount->name !== $site->name)
                                        <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $site->clientAccount->name }}</span>
                                    @endif
                                </span>
                                <span class="shrink-0 text-right text-xs">
                                    <span class="block">{{ $coverageStates[$row['status']] }}</span>
                                    @if ($row['required'] > 0)<span class="tabular-nums text-zinc-500">{{ $row['filled'] }}/{{ $row['required'] }} covered</span>@endif
                                </span>
                            </button>
                        @empty
                            <p class="p-3 text-sm text-zinc-500">No sites match these filters.</p>
                        @endforelse
                    </div>
                </x-section-card>
            </div>
        </div>

        <x-page-toolbar>
            <x-slot:tabs>
                <x-segment-control field="panelFilter" :active="$panelFilter" :options="['all' => 'All activity', 'on_duty' => 'Live guards', 'violations' => 'Geofence', 'idle' => 'Idle alerts']" />
            </x-slot:tabs>
            <x-slot:controls>
                <details wire:ignore.self wire:key="tracking-history-controls" class="min-w-0" @if ($guardFilter || $siteFilter) open @endif>
                    <summary class="cursor-pointer whitespace-nowrap text-sm font-medium">GPS history & map focus</summary>
                    <div class="mt-2 flex flex-wrap items-end gap-2">
                        <x-select wire:model.live="siteFilter" label="Focus site">
                            <option value="">All sites</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->name }}</option>
                            @endforeach
                        </x-select>
                        <x-select wire:model.live="guardFilter" label="History guard">
                            <option value="">Select guard</option>
                            @foreach ($historyGuards as $guard)
                                <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                            @endforeach
                        </x-select>
                        <x-input wire:model.live="historyDate" type="date" label="History date" />
                        <x-button type="button" size="sm" variant="secondary" wire:click="clearFocus">Clear map focus</x-button>
                    </div>
                </details>
            </x-slot:controls>
        </x-page-toolbar>

        <div class="grid items-start gap-2.5 lg:grid-cols-2">
            @if ($showGuards)
                <x-section-card compact title="Live guards" :description="$matchingDeployments->where('onDuty', true)->unique('assignment.guard_id')->count().' on duty'" flush>
                    @foreach ($matchingDeployments as $deployment)
                        @if ($deployment['guard'])
                            <button type="button" wire:click="openDeployment({{ $deployment['assignment']->id }})"
                                wire:key="tracking-deployment-{{ $deployment['assignment']->id }}" class="list-row w-full text-left">
                                <span class="min-w-0 flex-1">
                                    <strong class="block">{{ $deployment['guard']->full_name }}</strong>
                                    <span class="block text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $deployment['site']->name }} · {{ $deploymentStates[$deployment['status']] }}
                                    </span>
                                </span>
                                <span class="text-xs">{{ $deployment['location'] ? ($deployment['stale'] ? 'Last known GPS' : 'Recent GPS') : 'No GPS' }}</span>
                            </button>
                        @endif
                    @endforeach
                    @foreach ($liveGuards->whereNotIn('guard_id', $matchingDeployments->pluck('assignment.guard_id')) as $log)
                        <button type="button" wire:click="focusGuard({{ $log->guard_id }})" class="list-row w-full text-left" wire:key="live-guard-{{ $log->guard_id }}">
                            <span class="min-w-0 flex-1">
                                <strong class="block">{{ $log->assignedGuard?->full_name ?? 'Guard' }}</strong>
                                <span class="block text-xs text-zinc-500 dark:text-zinc-400">{{ $log->site?->name }} · Open check-in outside current deployment</span>
                            </span>
                        </button>
                    @endforeach
                    @if ($matchingDeployments->isEmpty() && $liveGuards->isEmpty())
                        <p class="p-3 text-sm text-zinc-500">No guards match this view.</p>
                    @endif
                </x-section-card>
            @endif

            @if ($showAlerts)
                <x-section-card compact title="Tracking alerts" flush>
                    <div class="max-h-72 overflow-y-auto">
                        @foreach($violations as $v)
                            <div class="list-row-start text-sm" wire:key="violation-{{ $v->id }}">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-red-500"></span>
                                <div class="min-w-0 flex-1 text-red-800 dark:text-red-300">
                                    Geofence: {{ $v->assignedGuard?->full_name }} left {{ $v->site?->name }}
                                    <span class="text-xs text-red-500 tabular-nums">({{ $v->distance_meters }}m)</span>
                                </div>
                                @if ($v->latitude && $v->longitude)
                                    <button type="button" class="table-action shrink-0" wire:click="focusCoords({{ $v->latitude }}, {{ $v->longitude }}, {{ $v->guard_id }})">Map</button>
                                @endif
                            </div>
                        @endforeach
                        @foreach($idleAlerts as $alert)
                            <div class="list-row-start text-sm" wire:key="idle-{{ $alert->id }}">
                                <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-amber-500"></span>
                                <div class="min-w-0 flex-1 text-amber-800 dark:text-amber-300">
                                    {{ $alert->assignedGuard?->full_name ?? 'Guard' }}
                                    · No GPS update for {{ \Carbon\CarbonInterval::minutes((int) $alert->idle_minutes)->cascade()->forHumans(['short' => true, 'parts' => 2]) }}
                                </div>
                                <button type="button" class="table-action shrink-0" wire:click="focusGuard({{ $alert->guard_id }})">Map</button>
                            </div>
                        @endforeach
                        @if($violations->isEmpty() && $idleAlerts->isEmpty())
                            <div class="p-3">
                                <x-empty-state compact title="No alerts" description="No recent geofence violations or unresolved idle alerts in this view." />
                            </div>
                        @endif
                    </div>
                </x-section-card>
            @endif
        </div>
    </x-page-shell>
</div>

@script
<script>
    if (window.Echo && @json(auth()->user()?->tenant_id)) {
        window.Echo.channel('tenant.{{ auth()->user()->tenant_id }}.dispatch')
            .listen('.sos.raised', () => $wire.$refresh());
    }
</script>
@endscript
