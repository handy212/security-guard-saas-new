<div>
    <x-page-shell title="Analytics" description="Operational KPIs and performance trends.">
        <x-slot:actions>
            <x-button wire:click="refreshSnapshot" size="sm" variant="secondary">Refresh snapshot</x-button>
        </x-slot:actions>

        @if($snapshot)
            <div class="grid grid-cols-4 gap-2">
                <x-stat-card compact label="Active guards" :value="$snapshot->active_guards" icon="guards" />
                <x-stat-card compact label="Patrol completion" :value="$snapshot->patrol_completion_rate.'%'" icon="patrols" tone="success" />
                <x-stat-card compact label="Missed patrols" :value="$snapshot->missed_patrols" icon="incidents" tone="danger" />
                <x-stat-card compact label="SLA performance" :value="$snapshot->client_sla_performance.'%'" icon="check" tone="info" />
            </div>

            <div class="grid grid-cols-4 gap-2">
                <x-stat-card compact label="Active sites" :value="$snapshot->active_sites" icon="sites" />
                <x-stat-card compact label="Late shifts" :value="$snapshot->late_shifts" icon="schedules" tone="warning" />
                <x-stat-card compact label="No-shows" :value="$snapshot->no_show_shifts" icon="guards" tone="warning" />
                <x-stat-card compact label="Revenue (day)" :value="'₦'.number_format($snapshot->revenue_total, 0)" icon="billing" />
            </div>

            <x-section-card title="30-day patrol completion trend">
                <div class="flex h-36 items-end gap-1">
                    @foreach($history->reverse() as $point)
                        <div class="flex flex-1 flex-col items-center gap-1">
                            <div class="w-full rounded-t bg-zinc-700" style="height: {{ max(6, (float) $point->patrol_completion_rate) }}%"></div>
                            <span class="text-[9px] text-zinc-400">{{ \Carbon\Carbon::parse($point->snapshot_date)->format('d') }}</span>
                        </div>
                    @endforeach
                </div>
            </x-section-card>
        @else
            <x-empty-state title="No analytics yet" description="Run a snapshot to populate KPIs.">
                <x-button wire:click="refreshSnapshot" size="sm" class="mt-3">Run first snapshot</x-button>
            </x-empty-state>
        @endif
    </x-page-shell>
</div>
