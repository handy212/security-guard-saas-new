<div>
    <x-page-header title="Analytics" description="Operational KPIs and performance trends.">
        <x-slot:actions>
            <x-button wire:click="refreshSnapshot" size="sm">Refresh snapshot</x-button>
        </x-slot:actions>
    </x-page-header>

    @if($snapshot)
        <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Active guards" :value="$snapshot->active_guards" />
            <x-stat-card label="Active sites" :value="$snapshot->active_sites" tone="info" />
            <x-stat-card label="Patrol completion" :value="$snapshot->patrol_completion_rate.'%'" tone="success" />
            <x-stat-card label="Revenue (day)" :value="'₦'.number_format($snapshot->revenue_total, 0)" />
            <x-stat-card label="Missed patrols" :value="$snapshot->missed_patrols" tone="danger" />
            <x-stat-card label="Late shifts" :value="$snapshot->late_shifts" tone="warning" />
            <x-stat-card label="No-shows" :value="$snapshot->no_show_shifts" tone="warning" />
            <x-stat-card label="SLA performance" :value="$snapshot->client_sla_performance.'%'" />
        </div>

        <div class="px-6 pb-8">
            <x-section-card title="30-day patrol completion trend">
                <div class="flex h-44 items-end gap-1.5">
                    @foreach($history->reverse() as $point)
                        <div class="group flex flex-1 flex-col items-center gap-1">
                            <div class="w-full rounded-t-md bg-gradient-to-t from-brand-600 to-brand-400 transition group-hover:from-brand-700"
                                 style="height: {{ max(6, (float) $point->patrol_completion_rate) }}%"
                                 title="{{ $point->snapshot_date }}: {{ $point->patrol_completion_rate }}%"></div>
                            <span class="text-[9px] text-slate-400">{{ \Carbon\Carbon::parse($point->snapshot_date)->format('d') }}</span>
                        </div>
                    @endforeach
                </div>
            </x-section-card>
        </div>
    @else
        <div class="p-6">
            <x-empty-state title="No analytics yet" description="Run a snapshot to populate KPI cards and trends." action="{{ route('analytics.dashboard') }}" actionLabel="Refresh now" />
        </div>
    @endif
</div>
