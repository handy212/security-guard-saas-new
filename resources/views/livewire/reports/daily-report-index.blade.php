<div>
    <x-page-shell title="Daily Activity Reports" description="Review guard shift summaries and approve for clients.">
        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$stats['total']" icon="plan" />
            <x-stat-card compact label="Pending" :value="$stats['pending']" icon="pause" :tone="$stats['pending'] ? 'warning' : 'default'" />
            <x-stat-card compact label="Approved" :value="$stats['approved']" icon="check" tone="success" />
            <x-stat-card compact label="Today" :value="$stats['today']" icon="schedules" tone="info" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search reports, sites, guards…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'submitted' => 'Pending', 'approved' => 'Approved', 'draft' => 'Draft']" />
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
                    <x-table.th>Report</x-table.th>
                    <x-table.th responsive="md">Site</x-table.th>
                    <x-table.th responsive="lg">Guard</x-table.th>
                    <x-table.th>Date</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th align="right">Actions</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($reports as $report)
                    <tr class="table-row-hover" wire:key="dar-{{ $report->id }}">
                        <x-table.td>
                            <div class="font-medium text-zinc-900">{{ $report->title }}</div>
                            @if ($report->summary)
                                <div class="mt-0.5 line-clamp-1 text-xs text-zinc-500">{{ $report->summary }}</div>
                            @endif
                        </x-table.td>
                        <x-table.td responsive="md" muted>{{ $report->site?->name ?? '—' }}</x-table.td>
                        <x-table.td responsive="lg" muted>{{ $report->assignedGuard?->full_name ?? '—' }}</x-table.td>
                        <x-table.td mono>{{ $report->report_date?->format('M j, Y') ?? '—' }}</x-table.td>
                        <x-table.td><x-badge :status="$report->status" /></x-table.td>
                        <x-table.td align="right">
                            @if($report->status !== 'approved')
                                <x-button size="sm" wire:click="approve({{ $report->id }})">Approve</x-button>
                            @else
                                <span class="text-xs text-zinc-400">Approved</span>
                            @endif
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="6">
                        <x-empty-state :title="$hasActiveFilters ? 'No matching reports' : 'No daily reports'" description="Guards submit activity reports from the field." />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$reports" />
    </x-page-shell>
</div>
