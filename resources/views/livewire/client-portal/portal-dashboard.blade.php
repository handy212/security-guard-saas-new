<div>
    <x-page-shell :show-header="false">

        <x-portal-page-header
            title="Service overview"
            :description="$clientAccount?->portal_welcome_message ?: 'Live visibility into guard coverage, patrols, incidents, and approved reports.'"
        />

        @if ($pendingApprovals->isNotEmpty())
            <section class="card-surface overflow-hidden border-amber-200/90 dark:border-amber-900/50" aria-label="Reports waiting for your decision">
                <div class="card-header border-amber-100 bg-amber-50/80 dark:border-amber-900/40 dark:bg-amber-950/40">
                    <div class="min-w-0 flex-1">
                        <h2 class="card-header-title text-amber-900 dark:text-amber-100">Reports waiting for your decision</h2>
                        <p class="card-header-meta text-amber-700/90 dark:text-amber-300">{{ $pendingApprovalCount }} pending · oldest first · approve or reject to release each report</p>
                    </div>
                    <x-button size="sm" :href="route('client-portal.approvals')">Review all</x-button>
                </div>
                <div class="divide-y divide-amber-100 dark:divide-amber-900/40">
                    @foreach ($pendingApprovals as $approval)
                        <div class="flex flex-wrap items-center justify-between gap-3 bg-amber-50/40 px-4 py-3 dark:bg-amber-950/20" wire:key="portal-approval-{{ $approval->id }}">
                            <div class="min-w-0">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ \Illuminate\Support\Str::headline(class_basename($approval->approvable_type)) }} #{{ $approval->approvable_id }}</div>
                                <div class="text-xs text-zinc-600 dark:text-zinc-300">
                                    {{ $approval->approvable?->site?->name ?? $approval->clientAccount?->name ?? 'Your account' }}
                                    · requested {{ $approval->created_at?->diffForHumans() ?? '—' }}
                                </div>
                            </div>
                            <a href="{{ route('client-portal.approvals') }}" class="page-link shrink-0 text-sm" wire:navigate>Review</a>
                        </div>
                    @endforeach
                </div>
            </section>
        @else
            <p class="text-sm text-zinc-500 dark:text-zinc-400" role="status">
                No reports are waiting for your approval.
                <a href="{{ route('client-portal.approvals') }}" class="page-link" wire:navigate>View approval history</a>
            </p>
        @endif

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
                        <a href="{{ route('client-portal.reports.show', $report->id) }}" class="page-link shrink-0 text-sm" wire:navigate>View report</a>
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
