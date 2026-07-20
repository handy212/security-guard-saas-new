<div>
    <x-page-shell :show-header="false">

        <x-portal-page-header
            title="Report Approvals"
            description="Review and sign off client-facing reports."
        />

        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$items->count()" icon="plan" />
            <x-stat-card compact label="Pending" :value="$items->where('status', 'pending')->count()" icon="pause" :tone="$items->where('status', 'pending')->count() ? 'warning' : 'success'" />
            <x-stat-card compact label="Approved" :value="$items->where('status', 'approved')->count()" icon="check" tone="success" />
            <x-stat-card compact label="Rejected" :value="$items->where('status', 'rejected')->count()" icon="incidents" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search by ID…" />

        <div class="space-y-2 sm:hidden">
            @forelse($items as $item)
                <div class="card-surface p-4" wire:key="approval-card-{{ $item->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="font-medium tabular-nums text-zinc-900 dark:text-zinc-100">#{{ $item->id }}</div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $item->clientAccount?->name ?? '—' }}</div>
                        </div>
                        <x-badge :status="$item->status" />
                    </div>
                    <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ class_basename($item->approvable_type) }} #{{ $item->approvable_id }}</div>
                    @if($item->status === 'pending')
                        <div class="mt-3 flex gap-2">
                            <x-button size="sm" wire:click="approve({{ $item->id }})" loading-text="…">Approve</x-button>
                            <x-button size="sm" variant="danger" wire:click="reject({{ $item->id }})" loading-text="…">Reject</x-button>
                        </div>
                    @else
                        <p class="mt-2 text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $item->approved_at?->format('M j, Y') ?? '—' }}</p>
                    @endif
                </div>
            @empty
                <x-empty-state title="No approvals" description="Client report approvals appear here." />
            @endforelse
        </div>

        <x-data-table title="Approvals" class="hidden sm:block">
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
                        <x-table.td mono class="tabular-nums">#{{ $item->id }}</x-table.td>
                        <x-table.td muted>{{ $item->clientAccount?->name ?? '—' }}</x-table.td>
                        <x-table.td muted>{{ class_basename($item->approvable_type) }} #{{ $item->approvable_id }}</x-table.td>
                        <x-table.td><x-badge :status="$item->status" /></x-table.td>
                        <x-table.td align="right">
                            @if($item->status === 'pending')
                                <div class="table-inline-actions justify-end">
                                    <x-button size="sm" wire:click="approve({{ $item->id }})" loading-text="…">Approve</x-button>
                                    <x-button size="sm" variant="danger" wire:click="reject({{ $item->id }})" loading-text="…">Reject</x-button>
                                </div>
                            @else
                                <span class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $item->approved_at?->format('M j, Y') ?? '—' }}</span>
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
