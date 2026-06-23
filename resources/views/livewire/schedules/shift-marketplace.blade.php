<div>
    <x-page-header title="Shift Marketplace" description="Open shift bids, swap requests, and supervisor approvals." />

    @php
        $pendingSwaps = $swaps->where('status', 'pending')->count();
    @endphp

    <div class="grid gap-4 px-6 pb-4 md:grid-cols-3">
        <x-stat-card label="Open bids" :value="$bids->where('status', 'pending')->count()" tone="info" />
        <x-stat-card label="Swap requests" :value="$swaps->count()" />
        <x-stat-card label="Pending approvals" :value="$pendingSwaps" :tone="$pendingSwaps ? 'warning' : 'success'" />
    </div>

    <div class="grid gap-6 px-6 pb-6 lg:grid-cols-2">
        <x-section-card title="Open shift bids" description="Guards bidding for unfilled shifts.">
            <x-data-table>
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Guard</th>
                        <th class="px-4 py-3">Shift</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bids as $bid)
                        <tr class="table-row-hover">
                            <td class="px-4 py-3 text-slate-900">{{ $bid->assignedGuard?->full_name ?? 'Guard #'.$bid->guard_id }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $bid->shift?->title ?? 'Shift #'.$bid->shift_id }}</td>
                            <td class="px-4 py-3"><x-badge :status="$bid->status" /></td>
                            <td class="px-4 py-3 text-right">
                                @if($bid->status === 'pending')
                                    <x-button size="sm" wire:click="approveBid({{ $bid->id }})">Approve</x-button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8"><x-empty-state title="No bids" description="Open shift bids appear here." /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </x-section-card>

        <x-section-card title="Swap requests" description="Guards requesting shift swaps.">
            <x-data-table>
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Requested by</th>
                        <th class="px-4 py-3">Replacement</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($swaps as $swap)
                        <tr class="table-row-hover">
                            <td class="px-4 py-3 text-slate-900">{{ $swap->requestedByGuard?->full_name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $swap->replacementGuard?->full_name ?? '—' }}</td>
                            <td class="px-4 py-3"><x-badge :status="$swap->status" /></td>
                            <td class="px-4 py-3 text-right">
                                @if($swap->status === 'pending')
                                    <x-button size="sm" wire:click="approveSwap({{ $swap->id }})">Approve</x-button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8"><x-empty-state title="No swaps" description="Shift swap requests appear here." /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </x-section-card>
    </div>
</div>
