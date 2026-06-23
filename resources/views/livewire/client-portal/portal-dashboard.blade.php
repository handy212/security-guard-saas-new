<div>
    <x-page-header title="Client Portal" description="Proof of service — shifts, patrols, incidents, and approved reports for your account." />

    <div class="grid gap-5 md:grid-cols-2">
        <x-section-card title="Recent shifts" description="Guard deployments at your sites">
            @forelse($shifts as $shift)
                <div class="flex items-center justify-between border-t border-slate-100 py-3 first:border-t-0">
                    <div>
                        <div class="font-medium">{{ $shift->site?->name }}</div>
                        <div class="text-sm text-slate-500">{{ $shift->starts_at?->format('M j, Y · H:i') }}</div>
                    </div>
                    <span class="text-xs font-semibold text-slate-500">{{ $shift->assignments->count() }} guard(s)</span>
                </div>
            @empty
                <x-empty-state title="No shifts" description="Scheduled shifts for your sites will appear here." />
            @endforelse
        </x-section-card>

        <x-section-card title="Approved reports">
            @forelse($reports as $report)
                <div class="border-t border-slate-100 py-3 first:border-t-0">
                    <div class="font-medium">{{ $report->site?->name }}</div>
                    <div class="text-sm text-slate-500">{{ $report->report_date?->format('M j, Y') ?? $report->created_at?->format('M j, Y') }}</div>
                </div>
            @empty
                <x-empty-state title="No reports yet" description="Approved daily activity reports will be listed here." />
            @endforelse
        </x-section-card>

        <x-section-card title="Incidents">
            @forelse($incidents as $incident)
                <div class="flex items-center justify-between border-t border-slate-100 py-3 first:border-t-0">
                    <div>
                        <div class="font-medium">{{ $incident->title }}</div>
                        <div class="text-sm text-slate-500">{{ $incident->site?->name }}</div>
                    </div>
                    <x-badge :status="$incident->status" />
                </div>
            @empty
                <x-empty-state title="No incidents" description="Security incidents shared with your account appear here." />
            @endforelse
        </x-section-card>

        <x-section-card title="Patrol proof">
            @forelse($patrols as $patrol)
                <div class="flex items-center justify-between border-t border-slate-100 py-3 first:border-t-0">
                    <div>
                        <div class="font-medium">{{ $patrol->route?->name ?? 'Patrol #'.$patrol->id }}</div>
                        <div class="text-sm text-slate-500">{{ $patrol->assignedGuard?->full_name }}</div>
                    </div>
                    <x-badge :status="$patrol->status" />
                </div>
            @empty
                <x-empty-state title="No patrols" description="Completed patrol sessions provide proof of guard rounds." />
            @endforelse
        </x-section-card>
    </div>
</div>
