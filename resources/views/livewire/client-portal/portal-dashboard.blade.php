<div>
    <x-page-shell :show-header="false">

        <x-portal-page-header
            title="Service overview"
            :description="$clientAccount?->portal_welcome_message ?: 'Live visibility into guard coverage, patrols, incidents, and approved reports.'"
        />

        <div class="kpi-grid">
            <x-stat-card compact label="Shifts" :value="$stats['shifts']" icon="schedules" />
            <x-stat-card compact label="Reports" :value="$stats['reports']" icon="plan" tone="success" />
            <x-stat-card compact label="Incidents" :value="$stats['incidents']" icon="incidents" :tone="$stats['incidents'] ? 'warning' : 'default'" />
            <x-stat-card compact label="Patrols done" :value="$stats['patrols']" icon="patrols" tone="info" />
            <x-stat-card compact label="Custom reports" :value="$stats['custom_reports']" icon="plan" />
        </div>

        <div class="page-grid-2">
            <x-section-card title="Recent shifts" description="Guard deployments at your sites" flush>
                @forelse($shifts as $shift)
                    <div class="list-row" wire:key="portal-shift-{{ $shift->id }}">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $shift->site?->name }}</div>
                            <div class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $shift->starts_at?->format('M j, Y · H:i') }}</div>
                        </div>
                        <span class="shrink-0 text-xs font-semibold tabular-nums text-zinc-500 dark:text-zinc-400">{{ $shift->assignments->count() }} guard(s)</span>
                    </div>
                @empty
                    <div class="p-3">
                        <x-empty-state title="No shifts" description="Scheduled shifts for your sites will appear here." />
                    </div>
                @endforelse
            </x-section-card>

            <x-section-card title="Approved reports" flush>
                @forelse($reports as $report)
                    <div class="list-row" wire:key="portal-report-{{ $report->id }}">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $report->site?->name }}</div>
                            <div class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $report->report_date?->format('M j, Y') ?? $report->created_at?->format('M j, Y') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="p-3">
                        <x-empty-state title="No reports yet" />
                    </div>
                @endforelse
            </x-section-card>

            <x-section-card title="Incidents" flush>
                @forelse($incidents as $incident)
                    <div class="list-row" wire:key="portal-incident-{{ $incident->id }}">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $incident->title }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $incident->site?->name }}</div>
                        </div>
                        <x-badge :status="$incident->status" />
                    </div>
                @empty
                    <div class="p-3">
                        <x-empty-state title="No incidents" />
                    </div>
                @endforelse
            </x-section-card>

            <x-section-card title="Patrol proof" flush>
                @forelse($patrols as $patrol)
                    <div class="list-row" wire:key="portal-patrol-{{ $patrol->id }}">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $patrol->route?->name ?? 'Patrol #'.$patrol->id }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $patrol->assignedGuard?->full_name }}
                                · <span class="tabular-nums">{{ $patrol->completion_percent ?? 0 }}%</span> complete
                            </div>
                        </div>
                        <x-badge :status="$patrol->status" />
                    </div>
                @empty
                    <div class="p-3">
                        <x-empty-state title="No patrols" />
                    </div>
                @endforelse
            </x-section-card>

            <x-section-card title="Custom reports" flush>
                @forelse($customReports as $report)
                    <div class="list-row" wire:key="portal-custom-{{ $report->id }}">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $report->template?->name }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $report->site?->name }} · {{ $report->submitted_at?->format('M j, Y') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="p-3">
                        <x-empty-state title="No custom reports" />
                    </div>
                @endforelse
            </x-section-card>
        </div>
    </x-page-shell>
</div>
