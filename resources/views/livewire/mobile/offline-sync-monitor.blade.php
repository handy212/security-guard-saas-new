<div>
    <x-page-header title="Offline Sync Monitor" description="Review and process guard mobile offline sync batches." />

    <div class="space-y-5 p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Filter by status (pending, processed, failed)…" class="max-w-sm" />
            <p class="text-xs text-slate-500">Batches queue patrol checkpoints, clock events, and SOS from the guard PWA.</p>
        </div>

        <x-data-table title="Sync batches">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Batch</th>
                    <th class="px-4 py-3">User</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Items</th>
                    <th class="px-4 py-3">Created</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="table-row-hover">
                        <td class="px-4 py-3 font-medium text-slate-900">#{{ $item->id }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3"><x-badge :status="$item->status" /></td>
                        <td class="px-4 py-3 text-slate-600">{{ is_array($item->payload) ? count($item->payload) : '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->created_at?->format('M j, H:i') }}</td>
                        <td class="px-4 py-3 text-right">
                            @if($item->status === 'pending')
                                <x-button size="sm" wire:click="process({{ $item->id }})">Process</x-button>
                            @else
                                <span class="text-xs text-slate-500">{{ $item->processed_at?->format('M j, H:i') ?? 'Done' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10"><x-empty-state title="No sync batches" description="Offline batches from guard devices appear here." /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>
    </div>
</div>
