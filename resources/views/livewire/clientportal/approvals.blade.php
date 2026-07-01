<div class="space-y-4">
    <x-portal-page-header
        title="Report Approvals"
        description="Review and sign off client-facing reports."
    />

    <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search by ID…" class="max-w-sm" />

    {{-- Mobile card list --}}
    <div class="space-y-3 sm:hidden">
        @forelse($items as $item)
            <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm" wire:key="approval-card-{{ $item->id }}">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-medium text-zinc-900">#{{ $item->id }}</div>
                        <div class="text-sm text-zinc-500">{{ $item->clientAccount?->name ?? '—' }}</div>
                    </div>
                    <x-badge :status="$item->status" />
                </div>
                <div class="mt-2 text-sm text-zinc-600">{{ class_basename($item->approvable_type) }} #{{ $item->approvable_id }}</div>
                @if($item->status === 'pending')
                    <div class="mt-3 flex gap-2">
                        <x-button size="sm" wire:click="approve({{ $item->id }})" loading-text="…">Approve</x-button>
                        <x-button size="sm" variant="danger" wire:click="reject({{ $item->id }})" loading-text="…">Reject</x-button>
                    </div>
                @else
                    <p class="mt-2 text-xs text-zinc-500">{{ $item->approved_at?->format('M j, Y') ?? '—' }}</p>
                @endif
            </div>
        @empty
            <x-empty-state title="No approvals" description="Client report approvals appear here." />
        @endforelse
    </div>

    {{-- Desktop table --}}
    <div class="hidden sm:block">
        <x-data-table title="Pending approvals">
            <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                <tr>
                    <th class="px-3 py-2">ID</th>
                    <th class="px-3 py-2">Client</th>
                    <th class="px-3 py-2">Report</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="table-row-hover" wire:key="approval-row-{{ $item->id }}">
                        <td class="px-3 py-2 font-medium text-zinc-900">#{{ $item->id }}</td>
                        <td class="px-3 py-2 text-zinc-600">{{ $item->clientAccount?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-zinc-600">{{ class_basename($item->approvable_type) }} #{{ $item->approvable_id }}</td>
                        <td class="px-3 py-2"><x-badge :status="$item->status" /></td>
                        <td class="px-3 py-2 text-right">
                            @if($item->status === 'pending')
                                <x-button size="sm" wire:click="approve({{ $item->id }})" loading-text="…">Approve</x-button>
                                <x-button size="sm" variant="danger" class="ml-2" wire:click="reject({{ $item->id }})" loading-text="…">Reject</x-button>
                            @else
                                <span class="text-xs text-zinc-500">{{ $item->approved_at?->format('M j, Y') ?? '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-8"><x-empty-state title="No approvals" description="Client report approvals appear here." /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>
    </div>
</div>
