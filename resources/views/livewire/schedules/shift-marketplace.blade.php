<div>
    @php
        $pendingSwaps = $swaps->where('status', 'pending')->count();
    @endphp

    <x-page-shell title="Shift Marketplace" description="Open shift bids and swap requests.">
        <div class="stat-grid">
            <x-stat-card compact label="Open bids" :value="$bids->where('status', 'pending')->count()" icon="schedules" tone="info" />
            <x-stat-card compact label="Swap requests" :value="$swaps->count()" icon="users" />
            <x-stat-card compact label="Pending approvals" :value="$pendingSwaps" icon="pause" :tone="$pendingSwaps ? 'warning' : 'success'" />
            <x-stat-card compact label="Total bids" :value="$bids->count()" icon="plan" />
        </div>

        <div class="page-grid-2">
            <x-section-card title="Open shift bids" description="Guards bidding for unfilled shifts.">
                <x-data-table>
                    <x-table.head>
                        <tr>
                            <x-table.th>Guard</x-table.th>
                            <x-table.th>Shift</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right">Actions</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse($bids as $bid)
                            <tr class="table-row-hover" wire:key="bid-{{ $bid->id }}">
                                <x-table.td><span class="font-medium text-zinc-900">{{ $bid->assignedGuard?->full_name ?? 'Guard #'.$bid->guard_id }}</span></x-table.td>
                                <x-table.td muted>{{ $bid->shift?->title ?? 'Shift #'.$bid->shift_id }}</x-table.td>
                                <x-table.td><x-badge :status="$bid->status" /></x-table.td>
                                <x-table.td align="right">
                                    @if($bid->status === 'pending')
                                        <x-button size="sm" wire:click="approveBid({{ $bid->id }})">Approve</x-button>
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="4">
                                <x-empty-state compact title="No bids" description="Open shift bids appear here." />
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>

            <x-section-card title="Swap requests" description="Guards requesting shift swaps.">
                <x-data-table>
                    <x-table.head>
                        <tr>
                            <x-table.th>Requested by</x-table.th>
                            <x-table.th>Replacement</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right">Actions</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse($swaps as $swap)
                            <tr class="table-row-hover" wire:key="swap-{{ $swap->id }}">
                                <x-table.td><span class="font-medium text-zinc-900">{{ $swap->requestedByGuard?->full_name ?? '—' }}</span></x-table.td>
                                <x-table.td muted>{{ $swap->replacementGuard?->full_name ?? '—' }}</x-table.td>
                                <x-table.td><x-badge :status="$swap->status" /></x-table.td>
                                <x-table.td align="right">
                                    @if($swap->status === 'pending')
                                        <x-button size="sm" wire:click="approveSwap({{ $swap->id }})">Approve</x-button>
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="4">
                                <x-empty-state compact title="No swaps" description="Shift swap requests appear here." />
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>
        </div>
    </x-page-shell>
</div>
