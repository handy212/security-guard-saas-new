<div>
    <x-page-shell title="Live Tracker" description="Real-time guard positions, tracking history, geofence violations, and idle alerts.">
        <div class="stat-grid">
            <x-stat-card compact label="On duty" :value="$liveGuards->count()" icon="guards" tone="success" />
            <x-stat-card compact label="Live positions" :value="$guardLocations->count()" icon="gps" tone="info" />
            <x-stat-card compact label="Geofence violations" :value="$violations->count()" icon="incidents" :tone="$violations->count() ? 'danger' : 'success'" />
            <x-stat-card compact label="Idle alerts" :value="$idleAlerts->count()" icon="dispatch" :tone="$idleAlerts->count() ? 'warning' : 'success'" />
        </div>

        <div class="flex flex-wrap gap-2">
            <x-select wire:model.live="siteFilter" label="Filter by site" class="min-w-48">
                <option value="">All sites</option>
                @foreach($sites as $site)
                    <option value="{{ $site->id }}">{{ $site->name }}</option>
                @endforeach
            </x-select>
            <x-select wire:model.live="guardFilter" label="History guard" class="min-w-48">
                <option value="">Select guard</option>
                @foreach($liveGuards as $log)
                    <option value="{{ $log->guard_id }}">{{ $log->assignedGuard?->full_name }}</option>
                @endforeach
            </x-select>
            <x-input wire:model.live="historyDate" type="date" label="History date" class="min-w-40" />
        </div>

        <x-map
            wire:key="tracking-map-{{ $siteFilter }}-{{ $guardFilter }}-{{ $historyDate }}"
            id="tracking-map"
            :lat="$mapCenter['lat']"
            :lng="$mapCenter['lng']"
            :markers="$markers"
            :polyline="$polyline"
            height="400px"
        />

        <div class="page-grid-2">
            <x-section-card title="Live guards">
                @forelse($liveGuards as $log)
                    <div class="border-t border-zinc-100 py-2 text-sm first:border-0">
                        <span class="font-medium">{{ $log->assignedGuard?->full_name }}</span>
                        <span class="text-zinc-500"> — {{ $log->site?->name }}</span>
                        <span class="text-xs text-zinc-400"> since {{ $log->clock_in_at?->format('H:i') }}</span>
                    </div>
                @empty
                    <x-empty-state title="No guards on duty" />
                @endforelse
            </x-section-card>

            <x-section-card title="Recent violations & idle">
                @foreach($violations as $v)
                    <div class="border-t border-zinc-100 py-2 text-sm first:border-0 text-red-700">
                        Geofence: {{ $v->assignedGuard?->full_name }} left {{ $v->site?->name }} ({{ $v->distance_meters }}m)
                    </div>
                @endforeach
                @foreach($idleAlerts as $alert)
                    <div class="border-t border-zinc-100 py-2 text-sm text-amber-700">
                        Idle: {{ $alert->assignedGuard?->full_name }} — {{ $alert->idle_minutes }} min
                    </div>
                @endforeach
                @if($violations->isEmpty() && $idleAlerts->isEmpty())
                    <x-empty-state title="No alerts" description="All guards are active and within geofence." />
                @endif
            </x-section-card>
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
