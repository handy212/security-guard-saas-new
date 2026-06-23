<div>
    <x-page-header title="Dispatch / Control Room" description="Live SOS, guard positions, and dispatch events." />

    <div class="grid gap-4 px-6 pb-4 md:grid-cols-3">
        <x-stat-card label="Active SOS" :value="$sosAlerts->count()" :tone="$sosAlerts->count() ? 'danger' : 'success'" />
        <x-stat-card label="Live guards" :value="$liveGuards->count()" tone="success" />
        <x-stat-card label="Dispatch events" :value="$events->count()" />
    </div>

    <div class="px-6 pb-6">
        <x-map id="dispatch-map" :lat="$mapCenter['lat']" :lng="$mapCenter['lng']" :markers="$markers" height="360px" />
    </div>

    <div class="grid gap-6 px-6 pb-6 lg:grid-cols-2">
        <x-section-card title="SOS Alerts" description="Active emergency alerts requiring acknowledgment.">
            @forelse($sosAlerts as $alert)
                <div class="flex items-center justify-between gap-3 border-t border-slate-100 py-3 first:border-0 first:pt-0">
                    <div class="min-w-0">
                        <div class="font-medium text-slate-900">{{ $alert->assignedGuard?->full_name ?? 'Guard' }}</div>
                        <div class="text-sm text-slate-500">{{ $alert->site?->name }} · <x-badge :status="$alert->status" /></div>
                    </div>
                    <x-button size="sm" variant="danger" wire:click="acknowledgeSos({{ $alert->id }})">Acknowledge</x-button>
                </div>
            @empty
                <x-empty-state title="No active SOS" description="All clear — no emergency alerts." />
            @endforelse
        </x-section-card>

        <x-section-card title="Live Guards" description="Guards currently clocked in on site.">
            @forelse($liveGuards as $log)
                <div class="border-t border-slate-100 py-3 text-sm first:border-0 first:pt-0">
                    <span class="font-medium text-slate-900">{{ $log->assignedGuard?->full_name ?? 'Guard' }}</span>
                    <span class="text-slate-500"> — {{ $log->site?->name }} since {{ $log->clock_in_at?->format('H:i') ?? $log->clock_in_at }}</span>
                </div>
            @empty
                <x-empty-state title="No guards on duty" description="No guards are currently clocked in." />
            @endforelse
        </x-section-card>
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
