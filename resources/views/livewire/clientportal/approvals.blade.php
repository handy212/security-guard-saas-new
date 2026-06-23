<div>
    <x-page-header title="Report Approvals" description="Review and sign off client-facing reports." />

    <div class="px-6 pb-4">
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search by ID…" class="w-full max-w-sm rounded-lg border px-3 py-2 text-sm">
    </div>

    <div class="px-6 pb-6">
        <x-data-table>
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Report</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($items as $item)
                    <tr>
                        <td class="px-4 py-3">#{{ $item->id }}</td>
                        <td class="px-4 py-3">{{ $item->clientAccount?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ class_basename($item->approvable_type) }} #{{ $item->approvable_id }}</td>
                        <td class="px-4 py-3">{{ $item->status }}</td>
                        <td class="px-4 py-3">
                            @if($item->status === 'pending')
                                <button wire:click="approve({{ $item->id }})" class="rounded bg-emerald-700 px-2 py-1 text-xs text-white">Approve</button>
                                <button wire:click="reject({{ $item->id }})" class="rounded bg-red-700 px-2 py-1 text-xs text-white">Reject</button>
                            @else
                                <span class="text-xs text-slate-500">{{ $item->approved_at }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No approvals pending.</td></tr>
                @endforelse
            </tbody>
        </x-data-table>
    </div>
</div>
