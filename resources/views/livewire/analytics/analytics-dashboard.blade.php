<div class="space-y-6 p-6">
    <div>
        <h1 class="text-2xl font-bold">Analytics Dashboard</h1>
        <p class="text-sm text-slate-500">Enterprise KPI dashboard.</p>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <h2 class="mb-3 font-semibold">Operational Workspace</h2>
        <p class="text-sm text-slate-600">Tracks active guards, active sites, missed patrols, incident severity, late/no-show shifts, patrol completion, SLA, revenue and guard scores.</p>
    </div>
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Snapshots</div><div class="mt-2 text-3xl font-black">{{ $history->count() }}</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Completion rate</div><div class="mt-2 text-3xl font-black">{{ $snapshot?->patrol_completion_rate ?? 0 }}%</div></div><div class="rounded-xl border bg-white p-4"><div class="text-xs uppercase text-slate-500">Revenue</div><div class="mt-2 text-3xl font-black">{{ number_format($snapshot?->revenue_total ?? 0,2) }}</div></div>
    </div>
</div>
