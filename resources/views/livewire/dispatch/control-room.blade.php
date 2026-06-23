<div>
    <x-page-header title="Dispatch / Control Room" description="Live SOS, guard positions, and dispatch events." />

    <div class="grid gap-4 p-6 md:grid-cols-3">
        <x-stat-card label="Active SOS" :value="$sosAlerts->count()" tone="danger" />
        <x-stat-card label="Live guards" :value="$liveGuards->count()" tone="success" />
        <x-stat-card label="Dispatch events" :value="$events->count()" />
    </div>

    <div class="px-6 pb-6">
        <x-map id="dispatch-map" :lat="$mapCenter['lat']" :lng="$mapCenter['lng']" :markers="$markers" height="360px" />
    </div>

    <div class="grid gap-6 px-6 pb-6 lg:grid-cols-2">
        <section class="rounded-xl border bg-white p-4">
            <h2 class="font-bold">SOS Alerts</h2>
            @forelse($sosAlerts as $alert)
                <div class="flex items-center justify-between border-t py-2">
                    <div>
                        <b>{{ $alert->assignedGuard?->full_name ?? 'Guard' }}</b>
                        <div class="text-sm text-slate-500">{{ $alert->site?->name }} · {{ $alert->status }}</div>
                    </div>
                    <button wire:click="acknowledgeSos({{ $alert->id }})" class="rounded bg-red-700 px-3 py-1 text-sm text-white">Acknowledge</button>
                </div>
            @empty
                <p class="py-4 text-sm text-slate-500">No active SOS alerts.</p>
            @endforelse
        </section>

        <section class="rounded-xl border bg-white p-4">
            <h2 class="font-bold">Live Guards</h2>
            @forelse($liveGuards as $log)
                <div class="border-t py-2 text-sm">{{ $log->assignedGuard?->full_name ?? 'Guard' }} — {{ $log->site?->name }} since {{ $log->clock_in_at }}</div>
            @empty
                <p class="py-4 text-sm text-slate-500">No guards currently clocked in.</p>
            @endforelse
        </section>
    </div>
</div>

@script
<script>
    if (window.Echo && @json(auth()->user()?->tenant_id)) {
        window.Echo.channel('tenant.{{ auth()->user()->tenant_id }}.dispatch')
            .listen('.sos.raised', () => $wire.$refresh())
            .listen('.dispatch.event', () => $wire.$refresh());
    }
</script>
@endscript
