<div>
    <x-page-header title="Report Approvals" description="Review and sign off client-facing reports." />

    <div class="space-y-5 p-6">
        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search by ID…" class="max-w-sm" />

        <x-data-table title="Pending approvals">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Report</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="table-row-hover">
                        <td class="px-4 py-3 font-medium text-slate-900">#{{ $item->id }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->clientAccount?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ class_basename($item->approvable_type) }} #{{ $item->approvable_id }}</td>
                        <td class="px-4 py-3"><x-badge :status="$item->status" /></td>
                        <td class="px-4 py-3 text-right">
                            @if($item->status === 'pending')
                                <x-button size="sm" wire:click="approve({{ $item->id }})">Approve</x-button>
                                <x-button size="sm" variant="danger" class="ml-2" wire:click="reject({{ $item->id }})">Reject</x-button>
                            @else
                                <span class="text-xs text-slate-500">{{ $item->approved_at?->format('M j, Y') ?? '—' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10"><x-empty-state title="No approvals" description="Client report approvals appear here." /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>
    </div>
</div>
