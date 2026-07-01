<div>
    <x-page-shell title="Offline Sync Monitor" description="Review and process guard mobile offline sync batches.">
        <x-settings-nav />

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <x-search-input wire:model.live.debounce.300ms="search" placeholder="Filter by status (pending, processed, failed)…" class="max-w-sm" />
            <p class="text-xs text-zinc-500">Batches queue patrol checkpoints, clock events, and SOS from the guard PWA.</p>
        </div>

        <x-data-table title="Sync batches">
            <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                <tr>
                    <th class="px-3 py-2">Batch</th>
                    <th class="px-3 py-2">User</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2">Items</th>
                    <th class="px-3 py-2">Created</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="table-row-hover">
                        <td class="px-3 py-2 font-medium text-zinc-900">#{{ $item->id }}</td>
                        <td class="px-3 py-2 text-zinc-600">{{ $item->user?->name ?? '—' }}</td>
                        <td class="px-3 py-2"><x-badge :status="$item->status" /></td>
                        <td class="px-3 py-2 text-zinc-600">{{ is_array($item->payload) ? count($item->payload) : '—' }}</td>
                        <td class="px-3 py-2 text-zinc-600">{{ $item->created_at?->format('M j, H:i') }}</td>
                        <td class="px-3 py-2 text-right">
                            @if($item->status === 'pending')
                                <x-button size="sm" wire:click="process({{ $item->id }})">Process</x-button>
                            @else
                                <span class="text-xs text-zinc-500">{{ $item->processed_at?->format('M j, H:i') ?? 'Done' }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-8"><x-empty-state title="No sync batches" description="Offline batches from guard devices appear here." /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>
    </x-page-shell>
</div>
