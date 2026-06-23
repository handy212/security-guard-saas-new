<div>
    <x-page-header title="Analytics" description="Operational KPIs and trends.">
        <x-slot:actions>
            <button wire:click="refreshSnapshot" class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">Refresh snapshot</button>
        </x-slot:actions>
    </x-page-header>

    @if($snapshot)
        <div class="grid gap-4 p-6 md:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Active guards" :value="$snapshot->active_guards" />
            <x-stat-card label="Active sites" :value="$snapshot->active_sites" tone="info" />
            <x-stat-card label="Patrol completion" :value="$snapshot->patrol_completion_rate.'%'" tone="success" />
            <x-stat-card label="Revenue (day)" :value="'$'.number_format($snapshot->revenue_total, 2)" />
            <x-stat-card label="Missed patrols" :value="$snapshot->missed_patrols" tone="danger" />
            <x-stat-card label="Late shifts" :value="$snapshot->late_shifts" tone="warning" />
            <x-stat-card label="No-shows" :value="$snapshot->no_show_shifts" tone="warning" />
            <x-stat-card label="SLA performance" :value="$snapshot->client_sla_performance.'%'" />
        </div>

        <div class="px-6 pb-6">
            <div class="rounded-xl border bg-white p-4">
                <h2 class="mb-4 font-bold">30-day patrol completion trend</h2>
                <div class="flex h-40 items-end gap-1">
                    @foreach($history->reverse() as $point)
                        <div class="flex-1 rounded-t bg-sky-500" style="height: {{ max(4, (float) $point->patrol_completion_rate) }}%" title="{{ $point->snapshot_date }}: {{ $point->patrol_completion_rate }}%"></div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="p-6">
            <x-empty-state title="No analytics yet" description="Run a snapshot to populate KPI cards." action="{{ route('analytics.dashboard') }}" actionLabel="Refresh now" />
        </div>
    @endif
</div>
