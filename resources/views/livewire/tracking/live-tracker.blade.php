<div wire:poll.15s>
    <x-page-shell title="Live Tracker" description="Live guard positions, history trails, geofence violations, and idle alerts.">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('dispatch.control-room')">Open dispatch</x-button>
        </x-slot:actions>

        <div class="stat-grid">
            <x-stat-card
                compact
                label="On duty"
                :value="$liveGuards->count()"
                icon="guards"
                tone="success"
                wire:click="applyStatFilter('on_duty')"
                class="cursor-pointer text-left"
                :active="$panelFilter === 'on_duty'"
            />
            <x-stat-card
                compact
                label="Live positions"
                :value="$guardLocations->count()"
                icon="gps"
                tone="info"
                wire:click="applyStatFilter('positions')"
                class="cursor-pointer text-left"
                :active="$panelFilter === 'positions'"
            />
            <x-stat-card
                compact
                label="Geofence violations"
                :value="$violations->count()"
                icon="incidents"
                :tone="$violations->count() ? 'danger' : 'success'"
                wire:click="applyStatFilter('violations')"
                class="cursor-pointer text-left"
                :active="$panelFilter === 'violations'"
            />
            <x-stat-card
                compact
                label="Idle alerts"
                :value="$idleAlerts->count()"
                icon="dispatch"
                :tone="$idleAlerts->count() ? 'warning' : 'success'"
                wire:click="applyStatFilter('idle')"
                class="cursor-pointer text-left"
                :active="$panelFilter === 'idle'"
            />
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

        <x-page-toolbar>
            <x-slot:controls>
                <x-select wire:model.live="siteFilter" label="Site" class="min-w-44">
                    <option value="">All sites</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-select>
                <x-select wire:model.live="guardFilter" label="History guard" class="min-w-44">
                    <option value="">Select guard</option>
                    @foreach($historyGuards as $guard)
                        <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model.live="historyDate" type="date" label="History date" class="min-w-40" />
                @if ($panelFilter !== 'all' || $focusLat)
                    <div class="flex items-end pb-0.5">
                        <button type="button" class="table-action" wire:click="clearFocus">Clear focus</button>
                    </div>
                @endif
            </x-slot:controls>
        </x-page-toolbar>

        <x-map
            id="tracking-map"
            :lat="$mapCenter['lat']"
            :lng="$mapCenter['lng']"
            :markers="$markers"
            :polyline="$polyline"
            :circles="$circles"
            :fit-bounds="$fitBounds"
            height="420px"
        />

        <div class="page-grid-2">
            @if ($showGuards)
                <x-section-card title="Live guards" :description="$liveGuards->count().' on duty'" flush>
                    @forelse($liveGuards as $log)
                        @php
                            $guard = $log->assignedGuard;
                            $initials = $guard
                                ? strtoupper(substr($guard->first_name, 0, 1).substr($guard->last_name, 0, 1))
                                : '?';
                            $location = $guardLocations->firstWhere('guard_id', $log->guard_id);
                        @endphp
                        <button
                            type="button"
                            wire:click="focusGuard({{ $log->guard_id }})"
                            class="list-row w-full text-left"
                            wire:key="live-guard-{{ $log->guard_id }}"
                        >
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-accent-50 text-xs font-semibold text-accent-700 dark:bg-accent-950 dark:text-accent-300">{{ $initials }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-medium text-zinc-900 dark:text-zinc-100">{{ $guard?->full_name ?? 'Guard' }}</span>
                                <span class="block truncate text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $log->site?->name ?? '—' }}
                                    · since <span class="tabular-nums">{{ $log->clock_in_at?->format('H:i') ?? '—' }}</span>
                                    @if ($location)
                                        · GPS {{ $location->recorded_at?->diffForHumans() }}
                                    @else
                                        · no recent GPS
                                    @endif
                                </span>
                            </span>
                        </button>
                    @empty
                        <div class="p-3">
                            <x-empty-state compact title="No guards on duty" />
                        </div>
                    @endforelse
                </x-section-card>
            @endif

            @if ($showAlerts)
                <x-section-card title="Recent violations & idle" flush>
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
                                Idle: {{ $alert->assignedGuard?->full_name }} — <span class="tabular-nums">{{ $alert->idle_minutes }}</span> min
                            </div>
                            <button type="button" class="table-action shrink-0" wire:click="focusGuard({{ $alert->guard_id }})">Map</button>
                        </div>
                    @endforeach
                    @if($violations->isEmpty() && $idleAlerts->isEmpty())
                        <div class="p-3">
                            <x-empty-state compact title="No alerts" description="All guards are active and within geofence." />
                        </div>
                    @endif
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
