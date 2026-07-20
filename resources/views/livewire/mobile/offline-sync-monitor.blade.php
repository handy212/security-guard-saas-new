<div>
    <x-page-shell title="Offline Sync Monitor" description="Review and process guard mobile offline sync batches.">
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <div class="stat-grid">
                <x-stat-card compact label="Batches" :value="$items->count()" icon="plan" />
                <x-stat-card compact label="Pending" :value="$items->where('status', 'pending')->count()" icon="pause" :tone="$items->where('status', 'pending')->count() ? 'warning' : 'default'" />
                <x-stat-card compact label="Processed" :value="$items->where('status', 'processed')->count()" icon="check" tone="success" />
                <x-stat-card compact label="Failed" :value="$items->where('status', 'failed')->count()" icon="incidents" :tone="$items->where('status', 'failed')->count() ? 'danger' : 'default'" />
            </div>

            <x-page-toolbar search="search" searchPlaceholder="Filter by status (pending, processed, failed)…">
                <x-slot:controls>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Patrol checkpoints, clock events, and SOS from the guard PWA.</p>
                </x-slot:controls>
            </x-page-toolbar>

            <x-data-table title="Sync batches">
                <x-table.head>
                    <tr>
                        <x-table.th>Batch</x-table.th>
                        <x-table.th>User</x-table.th>
                        <x-table.th>Status</x-table.th>
                        <x-table.th>Items</x-table.th>
                        <x-table.th>Created</x-table.th>
                        <x-table.th align="right">Actions</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse($items as $item)
                        <tr class="table-row-hover" wire:key="sync-{{ $item->id }}">
                            <x-table.td><span class="font-medium tabular-nums text-zinc-900 dark:text-zinc-100">#{{ $item->id }}</span></x-table.td>
                            <x-table.td muted>{{ $item->user?->name ?? '—' }}</x-table.td>
                            <x-table.td><x-badge :status="$item->status" /></x-table.td>
                            <x-table.td muted class="tabular-nums">{{ is_array($item->payload) ? count($item->payload) : '—' }}</x-table.td>
                            <x-table.td muted class="tabular-nums">{{ $item->created_at?->format('M j, H:i') }}</x-table.td>
                            <x-table.td align="right">
                                @if($item->status === 'pending')
                                    <x-button size="sm" wire:click="process({{ $item->id }})">Process</x-button>
                                @else
                                    <span class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $item->processed_at?->format('M j, H:i') ?? 'Done' }}</span>
                                @endif
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="6">
                            <x-empty-state title="No sync batches" description="Offline batches from guard devices appear here." />
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
