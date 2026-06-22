<div class="p-6 space-y-6" x-data="{ liveFeed: 'Connected' }">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Dispatch / Control Room</h1>
        <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800" x-text="liveFeed">Live</span>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><div class="text-sm text-slate-500">Active SOS</div><div class="text-3xl font-black">{{ $sosAlerts->count() }}</div></div>
        <div class="rounded-xl border bg-white p-4"><div class="text-sm text-slate-500">Live Guards</div><div class="text-3xl font-black">{{ $liveGuards->count() }}</div></div>
        <div class="rounded-xl border bg-white p-4"><div class="text-sm text-slate-500">Dispatch Events</div><div class="text-3xl font-black">{{ $events->count() }}</div></div>
    </div>
    <section class="rounded-xl border bg-white p-4">
        <h2 class="font-bold">SOS Alerts</h2>
        @foreach($sosAlerts as $alert)
            <div class="flex justify-between border-t py-2">
                <div>
                    <b>{{ $alert->assignedGuard?->full_name ?? 'Guard' }}</b>
                    <div class="text-sm text-slate-500">{{ $alert->site?->name }} · {{ $alert->latitude }},{{ $alert->longitude }} · {{ $alert->status }}</div>
                </div>
                <button wire:click="acknowledgeSos({{ $alert->id }})" class="rounded bg-red-700 px-3 text-white">Acknowledge</button>
            </div>
        @endforeach
    </section>
    <section class="rounded-xl border bg-white p-4">
        <h2 class="font-bold">Live Guards</h2>
        @foreach($liveGuards as $log)
            <div class="border-t py-2">{{ $log->assignedGuard?->full_name ?? 'Guard' }} — {{ $log->site?->name }} since {{ $log->clock_in_at }}</div>
        @endforeach
    </section>
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
