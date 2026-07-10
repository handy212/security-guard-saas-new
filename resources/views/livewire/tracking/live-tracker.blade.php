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
                class="cursor-pointer text-left transition hover:border-zinc-300"
                :active="$panelFilter === 'on_duty'"
            />
            <x-stat-card
                compact
                label="Live positions"
                :value="$guardLocations->count()"
                icon="gps"
                tone="info"
                wire:click="applyStatFilter('positions')"
                class="cursor-pointer text-left transition hover:border-zinc-300"
                :active="$panelFilter === 'positions'"
            />
            <x-stat-card
                compact
                label="Geofence violations"
                :value="$violations->count()"
                icon="incidents"
                :tone="$violations->count() ? 'danger' : 'success'"
                wire:click="applyStatFilter('violations')"
                class="cursor-pointer text-left transition hover:border-zinc-300"
                :active="$panelFilter === 'violations'"
            />
            <x-stat-card
                compact
                label="Idle alerts"
                :value="$idleAlerts->count()"
                icon="dispatch"
                :tone="$idleAlerts->count() ? 'warning' : 'success'"
                wire:click="applyStatFilter('idle')"
                class="cursor-pointer text-left transition hover:border-zinc-300"
                :active="$panelFilter === 'idle'"
            />
        </div>

        @if ($sosAlerts->isNotEmpty())
            <section class="mb-4 rounded-xl border border-red-200 bg-red-50/70 p-4">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold text-red-900">Open SOS</h2>
                    <a href="{{ route('dispatch.control-room', ['status' => 'active', 'priority' => 'critical']) }}" class="text-xs font-medium text-red-700 hover:underline">Open dispatch</a>
                </div>
                <div class="space-y-2">
                    @foreach ($sosAlerts as $alert)
                        <div class="flex flex-wrap items-center justify-between gap-2 text-sm" wire:key="track-sos-{{ $alert->id }}">
                            <div>
                                <span class="font-semibold text-red-900">{{ $alert->assignedGuard?->full_name ?? 'Guard' }}</span>
                                <span class="text-red-700"> · {{ $alert->site?->name ?? 'Unknown site' }}</span>
                                <span class="text-xs text-red-600"> · {{ $alert->raised_at?->diffForHumans() }}</span>
                            </div>
                            <div class="flex gap-2">
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

        <div class="mb-4 flex flex-wrap gap-3">
            <x-select wire:model.live="siteFilter" label="Filter by site" class="min-w-48">
                <option value="">All sites</option>
                @foreach($sites as $site)
                    <option value="{{ $site->id }}">{{ $site->name }}</option>
                @endforeach
            </x-select>
            <x-select wire:model.live="guardFilter" label="History guard" class="min-w-48">
                <option value="">Select guard</option>
                @foreach($historyGuards as $guard)
                    <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                @endforeach
            </x-select>
            <x-input wire:model.live="historyDate" type="date" label="History date" class="min-w-40" />
            @if ($panelFilter !== 'all' || $focusLat)
                <div class="flex items-end">
                    <button type="button" class="table-action mb-1" wire:click="clearFocus">Clear focus</button>
                </div>
            @endif
        </div>

        <x-map
            id="tracking-map"
            :lat="$mapCenter['lat']"
            :lng="$mapCenter['lng']"
            :markers="$markers"
            :polyline="$polyline"
            :circles="$circles"
            :fit-bounds="$fitBounds"
            height="420px"
            class="mb-4"
        />

        <div class="page-grid-2">
            @if ($showGuards)
                <x-section-card title="Live guards">
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
                            class="flex w-full items-center gap-3 border-t border-zinc-100 py-2.5 text-left text-sm first:border-0 hover:bg-zinc-50"
                            wire:key="live-guard-{{ $log->guard_id }}"
                        >
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-semibold text-zinc-700">{{ $initials }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-medium text-zinc-900">{{ $guard?->full_name ?? 'Guard' }}</span>
                                <span class="block truncate text-xs text-zinc-500">
                                    {{ $log->site?->name ?? '—' }}
                                    · since {{ $log->clock_in_at?->format('H:i') ?? '—' }}
                                    @if ($location)
                                        · GPS {{ $location->recorded_at?->diffForHumans() }}
                                    @else
                                        · no recent GPS
                                    @endif
                                </span>
                            </span>
                        </button>
                    @empty
                        <x-empty-state compact title="No guards on duty" />
                    @endforelse
                </x-section-card>
            @endif

            @if ($showAlerts)
                <x-section-card title="Recent violations & idle">
                    @foreach($violations as $v)
                        <div class="flex items-start justify-between gap-2 border-t border-zinc-100 py-2 text-sm text-red-700 first:border-0" wire:key="violation-{{ $v->id }}">
                            <div>
                                Geofence: {{ $v->assignedGuard?->full_name }} left {{ $v->site?->name }}
                                <span class="text-xs text-red-500">({{ $v->distance_meters }}m)</span>
                            </div>
                            @if ($v->latitude && $v->longitude)
                                <button type="button" class="table-action shrink-0" wire:click="focusCoords({{ $v->latitude }}, {{ $v->longitude }}, {{ $v->guard_id }})">Map</button>
                            @endif
                        </div>
                    @endforeach
                    @foreach($idleAlerts as $alert)
                        <div class="flex items-start justify-between gap-2 border-t border-zinc-100 py-2 text-sm text-amber-700 first:border-0" wire:key="idle-{{ $alert->id }}">
                            <div>
                                Idle: {{ $alert->assignedGuard?->full_name }} — {{ $alert->idle_minutes }} min
                            </div>
                            <button type="button" class="table-action shrink-0" wire:click="focusGuard({{ $alert->guard_id }})">Map</button>
                        </div>
                    @endforeach
                    @if($violations->isEmpty() && $idleAlerts->isEmpty())
                        <x-empty-state compact title="No alerts" description="All guards are active and within geofence." />
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
