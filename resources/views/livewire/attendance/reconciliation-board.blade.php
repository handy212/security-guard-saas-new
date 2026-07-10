<div>
    <x-page-shell title="Attendance reconciliation" description="Review late arrivals, early leaves, and no-shows.">
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>

            <x-flash-status />

            <x-page-toolbar>
                <x-slot:controls>
                    <x-filter-select wire:model.live="statusFilter" label="Status">
                        <option value="needs_review">Needs review</option>
                        <option value="reconciled">Reconciled</option>
                        <option value="all">All</option>
                    </x-filter-select>
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
                            <x-table.td class="font-medium">{{ $log->assignedGuard?->full_name }}</x-table.td>
                            <x-table.td muted>{{ $log->site?->name }}</x-table.td>
                            <x-table.td responsive="md" muted>{{ $log->clock_in_at?->format('M j, H:i') }}</x-table.td>
                            <x-table.td><x-badge :status="$log->status" /></x-table.td>
                            <x-table.td align="right">
                                @if (! $log->reconciled_at)
                                    <div class="flex justify-end gap-2">
                                        @if ($log->clock_in_at)
                                            <a href="{{ route('schedules.index', ['date' => $log->clock_in_at->toDateString()]) }}" class="text-xs font-medium text-accent-600 hover:underline">Day roster</a>
                                        @endif
                                        <x-button size="sm" wire:click="reconcile({{ $log->id }})">Reconcile</x-button>
                                    </div>
                                @else
                                    <span class="text-xs text-zinc-400">Reconciled</span>
                                @endif
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="5">
                            <x-empty-state compact title="Nothing to reconcile" description="All attendance records are up to date." />
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-pagination :paginator="$logs" />
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
