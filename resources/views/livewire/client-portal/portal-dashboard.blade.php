<div class="space-y-6">
    <x-page-header title="Client Portal" description="Assigned activity, reports, and proof of service." />

    <div class="grid gap-4 md:grid-cols-2">
        <section class="rounded-xl border bg-white p-4">
            <h2 class="font-bold">Recent shifts</h2>
            @forelse($shifts as $shift)
                <div class="border-t py-2 text-sm">
                    <div class="font-medium">{{ $shift->site?->name }}</div>
                    <div class="text-slate-500">{{ $shift->starts_at }} · {{ $shift->assignments->count() }} guard(s)</div>
                </div>
            @empty
                <p class="py-4 text-sm text-slate-500">No shifts to display.</p>
            @endforelse
        </section>

        <section class="rounded-xl border bg-white p-4">
            <h2 class="font-bold">Approved reports</h2>
            @forelse($reports as $report)
                <div class="border-t py-2 text-sm">
                    <div class="font-medium">{{ $report->site?->name }}</div>
                    <div class="text-slate-500">{{ $report->report_date ?? $report->created_at }}</div>
                </div>
            @empty
                <p class="py-4 text-sm text-slate-500">No approved reports yet.</p>
            @endforelse
        </section>

        <section class="rounded-xl border bg-white p-4">
            <h2 class="font-bold">Incidents</h2>
            @forelse($incidents as $incident)
                <div class="border-t py-2 text-sm">
                    <div class="font-medium">{{ $incident->title }}</div>
                    <div class="text-slate-500">{{ $incident->site?->name }} · {{ $incident->status }}</div>
                </div>
            @empty
                <p class="py-4 text-sm text-slate-500">No incidents reported.</p>
            @endforelse
        </section>

        <section class="rounded-xl border bg-white p-4">
            <h2 class="font-bold">Patrol proof</h2>
            @forelse($patrols as $patrol)
                <div class="border-t py-2 text-sm">
                    <div class="font-medium">{{ $patrol->route?->name ?? 'Patrol #'.$patrol->id }}</div>
                    <div class="text-slate-500">{{ $patrol->assignedGuard?->full_name }} · {{ $patrol->status }}</div>
                </div>
            @empty
                <p class="py-4 text-sm text-slate-500">No patrol sessions yet.</p>
            @endforelse
        </section>
    </div>
</div>
