<div>
    <x-page-shell title="Report Approvals" description="Review and sign off client-facing reports.">
        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$items->count()" icon="plan" />
            <x-stat-card compact label="Pending" :value="$items->where('status', 'pending')->count()" icon="pause" :tone="$items->where('status', 'pending')->count() ? 'warning' : 'success'" />
            <x-stat-card compact label="Approved" :value="$items->where('status', 'approved')->count()" icon="check" tone="success" />
            <x-stat-card compact label="Rejected" :value="$items->where('status', 'rejected')->count()" icon="incidents" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search by ID…" />

        <x-data-table title="Pending approvals">
            <x-table.head>
                <tr>
                    <x-table.th>ID</x-table.th>
                    <x-table.th>Client</x-table.th>
                    <x-table.th>Report</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th align="right">Actions</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($items as $item)
                    <tr class="table-row-hover" wire:key="approval-{{ $item->id }}">
                        <x-table.td mono>#{{ $item->id }}</x-table.td>
                        <x-table.td muted>{{ $item->clientAccount?->name ?? '—' }}</x-table.td>
                        <x-table.td muted>{{ class_basename($item->approvable_type) }} #{{ $item->approvable_id }}</x-table.td>
                        <x-table.td><x-badge :status="$item->status" /></x-table.td>
                        <x-table.td align="right">
                            @if($item->status === 'pending')
                                <div class="flex justify-end gap-2">
                                    <x-button size="sm" wire:click="approve({{ $item->id }})">Approve</x-button>
                                    <x-button size="sm" variant="danger" wire:click="reject({{ $item->id }})">Reject</x-button>
                                </div>
                            @else
                                <span class="text-xs text-zinc-500">{{ $item->approved_at?->format('M j, Y') ?? '—' }}</span>
                            @endif
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="5">
                        <x-empty-state title="No approvals" description="Client report approvals appear here." />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>
    </x-page-shell>
</div>
