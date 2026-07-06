<div>
    <x-page-shell title="Shift Exchange" description="Approve guard shift swap requests.">
        <x-schedules-nav />
        <x-flash-status />

        <x-page-toolbar class="mb-4">
            <x-slot:tabs>
                <x-segment-control field="swapFilter" :active="$swapFilter" :options="['pending' => 'Pending', 'all' => 'All', 'approved' => 'Approved', 'rejected' => 'Rejected']" />
            </x-slot:tabs>
            @if($pendingSwapCount)
                <x-slot:controls>
                    <span class="text-xs font-medium text-amber-700">{{ $pendingSwapCount }} awaiting approval</span>
                </x-slot:controls>
            @endif
        </x-page-toolbar>

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Requested by</x-table.th>
                    <x-table.th>Shift</x-table.th>
                    <x-table.th>Replacement</x-table.th>
                    <x-table.th>Reason</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th align="right">Actions</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($swaps as $swap)
                    <tr wire:key="swap-{{ $swap->id }}">
                        <x-table.td>{{ $swap->requestedByGuard?->full_name }}</x-table.td>
                        <x-table.td muted>{{ $swap->shiftAssignment?->shift?->title ?? '—' }} · {{ $swap->shiftAssignment?->shift?->site?->name }}</x-table.td>
                        <x-table.td>{{ $swap->replacementGuard?->full_name ?? '—' }}</x-table.td>
                        <x-table.td muted>{{ $swap->reason ?: '—' }}</x-table.td>
                        <x-table.td><x-badge :status="$swap->status" /></x-table.td>
                        <x-table.td align="right">
                            @if(\App\Support\EnumHelper::is($swap->status, 'pending'))
                                <x-button size="sm" wire:click="approveSwap({{ $swap->id }})">Approve</x-button>
                                <x-button size="sm" variant="secondary" wire:click="rejectSwap({{ $swap->id }})">Reject</x-button>
                            @endif
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="6"><x-empty-state title="No shift exchanges" /></x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>
    </x-page-shell>
</div>
