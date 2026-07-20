<div>
    <x-page-shell
        title="Attendance reconciliation"
        description="Review late arrivals, early leaves, and no-shows."
        :breadcrumbs="[
            ['label' => 'Scheduler', 'href' => route('schedules.index')],
            ['label' => 'Reconciliation'],
        ]"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('schedules.attendance')">Attendance</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>

            <x-flash-status />

            <div class="stat-grid">
                <x-stat-card compact label="Needs review" :value="$stats['needs_review']" icon="incidents" :tone="$stats['needs_review'] ? 'warning' : 'success'" wire:click="applyStatFilter('needs_review')" class="cursor-pointer text-left" :active="$statusFilter === 'needs_review'" />
                <x-stat-card compact label="Late" :value="$stats['late']" icon="pause" :tone="$stats['late'] ? 'warning' : 'default'" />
                <x-stat-card compact label="No-show" :value="$stats['no_show']" icon="guards" :tone="$stats['no_show'] ? 'danger' : 'default'" />
                <x-stat-card compact label="Reconciled" :value="$stats['reconciled']" icon="check" tone="success" wire:click="applyStatFilter('reconciled')" class="cursor-pointer text-left" :active="$statusFilter === 'reconciled'" />
            </div>

            <x-page-toolbar search="search" searchPlaceholder="Search guard or site…">
                <x-slot:tabs>
                    <x-segment-control field="statusFilter" :active="$statusFilter" :options="['needs_review' => 'Needs review', 'reconciled' => 'Reconciled', 'all' => 'All']" />
                </x-slot:tabs>
                <x-slot:controls>
                    @if ($hasActiveFilters)
                        <button type="button" wire:click="clearFilters" class="table-action">Clear filters</button>
                    @endif
                </x-slot:controls>
            </x-page-toolbar>

            <x-data-table>
                <x-table.head>
                    <tr>
                        <x-table.th>Guard</x-table.th>
                        <x-table.th>Site</x-table.th>
                        <x-table.th responsive="md">Clock in</x-table.th>
                        <x-table.th>Status</x-table.th>
                        <x-table.th align="right"></x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse($logs as $log)
                        <tr class="table-row-hover" wire:key="recon-{{ $log->id }}">
                            <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $log->assignedGuard?->full_name }}</x-table.td>
                            <x-table.td muted>{{ $log->site?->name }}</x-table.td>
                            <x-table.td responsive="md" muted class="tabular-nums">{{ $log->clock_in_at?->format('M j, H:i') }}</x-table.td>
                            <x-table.td><x-badge :status="$log->status" /></x-table.td>
                            <x-table.td align="right">
                                @if (! $log->reconciled_at)
                                    <div class="table-inline-actions justify-end">
                                        @if ($log->clock_in_at)
                                            <a href="{{ route('schedules.index', ['date' => $log->clock_in_at->toDateString()]) }}" class="table-action" wire:navigate>Day roster</a>
                                        @endif
                                        <a href="{{ route('schedules.attendance', array_filter(['date' => $log->clock_in_at?->toDateString()])) }}" class="table-action" wire:navigate>Attendance</a>
                                        <x-button size="sm" wire:click="reconcile({{ $log->id }})">Reconcile</x-button>
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400">Reconciled</span>
                                @endif
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="5">
                            <x-empty-state
                                compact
                                :title="$statusFilter === 'needs_review' ? 'Nothing to reconcile' : 'No matching records'"
                                :description="$statusFilter === 'needs_review' ? 'All attendance exceptions are up to date.' : 'Try adjusting your filters.'"
                            >
                                <x-slot:actions>
                                    <x-button size="sm" variant="secondary" :href="route('schedules.attendance')">View attendance</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-pagination :paginator="$logs" />
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
