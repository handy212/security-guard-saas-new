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

            <x-section-card title="Recent daily reports">
                @forelse ($recentDaily as $report)
                    <div class="flex items-center justify-between gap-3 border-t border-zinc-100 py-2.5 text-sm first:border-0 dark:border-zinc-800" wire:key="dar-{{ $report->id }}">
                        <div class="min-w-0">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $report->title }}</div>
                            <div class="truncate text-xs text-zinc-500">{{ $report->site?->name }} · {{ $report->assignedGuard?->full_name }}</div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <span class="text-xs text-zinc-500">{{ $report->report_date?->format('M j') }}</span>
                            <x-badge :status="$report->status" />
                        </div>
                    </div>
                @empty
                    <x-empty-state compact title="No reports yet" description="Guards submit daily activity from the field.">
                        <x-slot:actions>
                            <x-button size="sm" :href="route('reports.daily')">Daily reports</x-button>
                        </x-slot:actions>
                    </x-empty-state>
                @endforelse
            </x-section-card>

            <div class="flex flex-wrap gap-2">
                <x-button :href="route('reports.daily')">Daily reports</x-button>
                <x-button variant="secondary" :href="route('reports.templates')">Custom templates</x-button>
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
