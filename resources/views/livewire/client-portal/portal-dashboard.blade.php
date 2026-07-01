<div>
    <x-page-shell title="Service overview" description="Live visibility into guard coverage, patrols, incidents, and approved reports.">
        <div class="grid grid-cols-4 gap-2">
            <x-stat-card compact label="Shifts" :value="$stats['shifts']" icon="schedules" />
            <x-stat-card compact label="Reports" :value="$stats['reports']" icon="plan" tone="success" />
            <x-stat-card compact label="Incidents" :value="$stats['incidents']" icon="incidents" :tone="$stats['incidents'] ? 'warning' : 'default'" />
            <x-stat-card compact label="Patrols done" :value="$stats['patrols']" icon="patrols" tone="info" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-section-card title="Recent shifts" description="Guard deployments at your sites">
                @forelse($shifts as $shift)
                    <div class="flex items-center justify-between border-t border-zinc-100 py-3 first:border-t-0">
                        <div>
                            <div class="font-medium">{{ $shift->site?->name }}</div>
                            <div class="text-sm text-zinc-500">{{ $shift->starts_at?->format('M j, Y · H:i') }}</div>
                        </div>
                        <span class="text-xs font-semibold text-zinc-500">{{ $shift->assignments->count() }} guard(s)</span>
                    </div>
                @empty
                    <x-empty-state title="No shifts" description="Scheduled shifts for your sites will appear here." />
                @endforelse
            </x-section-card>

            <x-section-card title="Approved reports">
                @forelse($reports as $report)
                    <div class="border-t border-zinc-100 py-3 first:border-t-0">
                        <div class="font-medium">{{ $report->site?->name }}</div>
                        <div class="text-sm text-zinc-500">{{ $report->report_date?->format('M j, Y') ?? $report->created_at?->format('M j, Y') }}</div>
                    </div>
                @empty
                    <x-empty-state title="No reports yet" />
                @endforelse
            </x-section-card>

            <x-section-card title="Incidents">
                @forelse($incidents as $incident)
                    <div class="flex items-center justify-between border-t border-zinc-100 py-3 first:border-t-0">
                        <div>
                            <div class="font-medium">{{ $incident->title }}</div>
                            <div class="text-sm text-zinc-500">{{ $incident->site?->name }}</div>
                        </div>
                        <x-badge :status="$incident->status" />
                    </div>
                @empty
                    <x-empty-state title="No incidents" />
                @endforelse
            </x-section-card>

            <x-section-card title="Patrol proof">
                @forelse($patrols as $patrol)
                    <div class="flex items-center justify-between border-t border-zinc-100 py-3 first:border-t-0">
                        <div>
                            <div class="font-medium">{{ $patrol->route?->name ?? 'Patrol #'.$patrol->id }}</div>
                            <div class="text-sm text-zinc-500">{{ $patrol->assignedGuard?->full_name }}</div>
                        </div>
                        <x-badge :status="$patrol->status" />
                    </div>
                @empty
                    <x-empty-state title="No patrols" />
                @endforelse
            </x-section-card>
        </div>
    </x-page-shell>
</div>
