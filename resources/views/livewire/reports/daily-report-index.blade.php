<div>
    <x-page-shell title="Daily Activity Reports" description="Review guard shift summaries and approve for clients.">
        <x-stat-grid>
            <x-stat-card compact label="Total" :value="$stats['total']" icon="plan" />
            <x-stat-card compact label="Pending" :value="$stats['pending']" icon="pause" :tone="$stats['pending'] ? 'warning' : 'default'" />
            <x-stat-card compact label="Approved" :value="$stats['approved']" icon="check" tone="success" />
            <x-stat-card compact label="Today" :value="$stats['today']" icon="schedules" tone="info" />
        </x-stat-grid>

        <x-page-toolbar search="search" searchPlaceholder="Search reports, sites, guards…">
            <x-slot:tabs>
                <x-segment-control model="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'submitted' => 'Pending', 'approved' => 'Approved', 'draft' => 'Draft']" />
            </x-slot:tabs>
            <x-slot:controls>
                @if ($hasActiveFilters)
                    <button type="button" wire:click="clearFilters" class="text-xs font-medium text-zinc-500 hover:text-zinc-800">Clear filters</button>
                @endif
            </x-slot:controls>
        </x-page-toolbar>

        <x-data-table>
            <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                <tr>
                    <th class="px-3 py-2">Report</th>
                    <th class="hidden px-3 py-2 md:table-cell">Site</th>
                    <th class="hidden px-3 py-2 lg:table-cell">Guard</th>
                    <th class="px-3 py-2">Date</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $report)
                    <tr class="table-row-hover" wire:key="dar-{{ $report->id }}">
                        <td class="px-3 py-2">
                            <div class="font-medium text-zinc-900">{{ $report->title }}</div>
                            @if ($report->summary)
                                <div class="mt-0.5 line-clamp-1 text-xs text-zinc-500">{{ $report->summary }}</div>
                            @endif
                        </td>
                        <td class="hidden px-3 py-2 text-zinc-600 md:table-cell">{{ $report->site?->name ?? '—' }}</td>
                        <td class="hidden px-3 py-2 text-zinc-600 lg:table-cell">{{ $report->assignedGuard?->full_name ?? '—' }}</td>
                        <td class="px-3 py-2 text-xs text-zinc-600">{{ $report->report_date?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-3 py-2"><x-badge :status="$report->status" /></td>
                        <td class="px-3 py-2 text-right">
                            @if($report->status !== 'approved')
                                <x-button size="sm" wire:click="approve({{ $report->id }})">Approve</x-button>
                            @else
                                <span class="text-xs text-zinc-400">Approved</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-8"><x-empty-state :title="$hasActiveFilters ? 'No matching reports' : 'No daily reports'" description="Guards submit activity reports from the field." /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$reports" />
    </x-page-shell>
</div>
