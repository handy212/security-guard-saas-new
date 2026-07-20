<div>
    <x-page-shell
        title="Reports"
        description="Daily activity reports and custom field templates."
        :breadcrumbs="[['label' => 'Reports']]"
    >
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-reports-nav /></x-slot:sidebar>

            <div class="stat-grid">
                <x-stat-card compact label="Daily reports" :value="$stats['daily_total']" icon="reports" :href="route('reports.daily')" />
                <x-stat-card compact label="Pending review" :value="$stats['daily_pending']" icon="pause" :tone="$stats['daily_pending'] ? 'warning' : 'default'" :href="route('reports.daily', ['status' => 'submitted'])" />
                <x-stat-card compact label="Today" :value="$stats['daily_today']" icon="schedules" tone="info" />
                <x-stat-card compact label="Templates" :value="$stats['templates']" icon="plan" :href="route('reports.templates')" />
            </div>

            <div class="flex flex-wrap gap-1.5">
                <a href="{{ route('reports.daily') }}" class="quick-action" wire:navigate>Daily reports</a>
                <a href="{{ route('reports.templates') }}" class="quick-action" wire:navigate>Custom templates</a>
            </div>

            <x-section-card title="Recent daily reports" flush>
                <x-slot:actions>
                    <a href="{{ route('reports.daily') }}" class="page-link" wire:navigate>View all</a>
                </x-slot:actions>
                @forelse ($recentDaily as $report)
                    <a href="{{ route('reports.daily') }}" wire:navigate class="list-row" wire:key="dar-{{ $report->id }}">
                        <div class="min-w-0 flex-1">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $report->title }}</div>
                            <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $report->site?->name }} · {{ $report->assignedGuard?->full_name }}</div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $report->report_date?->format('M j') }}</span>
                            <x-badge :status="$report->status" />
                        </div>
                    </a>
                @empty
                    <div class="p-3">
                        <x-empty-state compact title="No reports yet" description="Guards submit daily activity from the field.">
                            <x-slot:actions>
                                <x-button size="sm" :href="route('reports.daily')">Daily reports</x-button>
                            </x-slot:actions>
                        </x-empty-state>
                    </div>
                @endforelse
            </x-section-card>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
