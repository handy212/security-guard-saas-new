<div class="p-6 space-y-6">
    <div><h1 class="text-2xl font-bold">Operations Dashboard</h1><p class="text-sm text-slate-500">Live command center for guards, sites, incidents, patrols, and attendance.</p></div>
    <div class="grid gap-4 md:grid-cols-6">
        @foreach($stats as $label => $value)
            <div class="rounded-xl border bg-white p-4 shadow-sm"><div class="text-xs uppercase text-slate-500">{{ str_replace('_',' ', $label) }}</div><div class="mt-2 text-3xl font-black">{{ $value }}{{ $label === 'patrol_completion' ? '%' : '' }}</div></div>
        @endforeach
    </div>
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="rounded-xl border bg-white p-4"><h2 class="font-bold mb-3">Latest Incidents</h2>@foreach($incidents as $incident)<div class="border-t py-2"><b>{{ $incident->title }}</b><div class="text-sm text-slate-500">{{ $incident->site?->name }} · {{ $incident->severity }} · {{ $incident->status }}</div></div>@endforeach</section>
        <section class="rounded-xl border bg-white p-4"><h2 class="font-bold mb-3">Live Attendance</h2>@foreach($attendance as $log)<div class="border-t py-2"><b>{{ $log->guard?->full_name ?? 'Guard' }}</b><div class="text-sm text-slate-500">{{ $log->site?->name }} · {{ $log->status }} · {{ $log->clock_in_at }}</div></div>@endforeach</section>
    </div>
</div>
