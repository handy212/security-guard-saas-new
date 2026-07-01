<div>
    <x-page-shell title="Dispatch" description="Live SOS, guard positions, and dispatch events.">
        <x-stat-grid>
            <x-stat-card compact label="Active SOS" :value="$sosAlerts->count()" icon="incidents" :tone="$sosAlerts->count() ? 'danger' : 'success'" />
            <x-stat-card compact label="Live guards" :value="$liveGuards->count()" icon="guards" tone="success" />
            <x-stat-card compact label="Events" :value="$events->count()" icon="dispatch" />
            <x-stat-card compact label="Map markers" :value="count($markers)" icon="gps" tone="info" />
        </x-stat-grid>

        <script type="application/json" id="dispatch-map-data">{!! json_encode(['lat' => $mapCenter['lat'], 'lng' => $mapCenter['lng'], 'zoom' => 13, 'markers' => $markers]) !!}</script>

        <x-map id="dispatch-map" :lat="$mapCenter['lat']" :lng="$mapCenter['lng']" :markers="$markers" height="min(50vh, 420px)" />

        <div class="grid gap-4 lg:grid-cols-2">
            <x-section-card title="SOS Alerts">
                @forelse($sosAlerts as $alert)
                    <div class="flex items-center justify-between gap-2 border-t border-zinc-100 py-2 text-sm first:border-0">
                        <div>
                            <div class="font-medium">{{ $alert->assignedGuard?->full_name ?? 'Guard' }}</div>
                            <div class="text-xs text-zinc-500">{{ $alert->site?->name }}</div>
                        </div>
                        <x-button size="sm" variant="danger" wire:click="acknowledgeSos({{ $alert->id }})" loading-text="…">Ack</x-button>
                    </div>
                @empty
                    <x-empty-state title="No active SOS" />
                @endforelse
            </x-section-card>

            <x-section-card title="Live Guards">
                @forelse($liveGuards as $log)
                    <div class="border-t border-zinc-100 py-2 text-sm first:border-0">
                        <span class="font-medium">{{ $log->assignedGuard?->full_name ?? 'Guard' }}</span>
                        <span class="text-zinc-500"> — {{ $log->site?->name }}</span>
                    </div>
                @empty
                    <x-empty-state title="No guards on duty" />
                @endforelse
            </x-section-card>
        </div>
    </x-page-shell>
</div>

@script
<script>
    const refreshDispatchMap = () => {
        const dataEl = document.getElementById('dispatch-map-data');
        if (! dataEl || ! window.guardOpsUpdateMap) return;
        const config = JSON.parse(dataEl.textContent);
        window.guardOpsUpdateMap('dispatch-map', config);
    };

    refreshDispatchMap();

    if (window.Echo && @json(auth()->user()?->tenant_id)) {
        window.Echo.channel('tenant.{{ auth()->user()->tenant_id }}.dispatch')
            .listen('.sos.raised', () => {
                $wire.$refresh();
                setTimeout(refreshDispatchMap, 150);
            })
            .listen('.dispatch.event', () => {
                $wire.$refresh();
                setTimeout(refreshDispatchMap, 150);
            });
    }

    Livewire.hook('commit', ({ succeed }) => {
        succeed(() => {
            if (document.getElementById('dispatch-map-data')) {
                refreshDispatchMap();
            }
        });
    });
</script>
@endscript
