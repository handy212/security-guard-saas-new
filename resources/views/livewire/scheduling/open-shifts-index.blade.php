<div>
    <x-page-shell title="Open Shifts" description="Unfilled shifts and guard bids.">
        <x-schedules-nav />
        <x-flash-status />

        <div class="grid gap-4 lg:grid-cols-2">
            <x-section-card title="Shifts needing guards">
                @forelse($openShifts as $shift)
                    <div class="border-t py-2 text-sm first:border-0" wire:key="open-{{ $shift->id }}">
                        <div class="font-medium">{{ $shift->title }}</div>
                        <div class="text-xs text-zinc-500">{{ $shift->site?->name }} · {{ $shift->starts_at?->format('M j, H:i') }}</div>
                        <div class="mt-1 text-xs text-amber-700">{{ $shift->assignments->count() }}/{{ $shift->required_guards }} filled · <x-badge :status="$shift->status" /></div>
                    </div>
                @empty
                    <x-empty-state compact title="All shifts staffed" />
                @endforelse
            </x-section-card>

            <x-section-card title="Guard bids">
                <x-data-table>
                    <x-table.head><tr><x-table.th>Guard</x-table.th><x-table.th>Shift</x-table.th><x-table.th>Status</x-table.th><x-table.th align="right"></x-table.th></tr></x-table.head>
                    <tbody>
                        @forelse($bids as $bid)
                            <tr wire:key="bid-{{ $bid->id }}">
                                <x-table.td>{{ $bid->assignedGuard?->full_name }}</x-table.td>
                                <x-table.td muted>{{ $bid->shift?->title }}</x-table.td>
                                <x-table.td><x-badge :status="$bid->status" /></x-table.td>
                                <x-table.td align="right">
                                    @if($bid->status === 'pending')
                                        <x-button size="sm" wire:click="approveBid({{ $bid->id }})">Approve</x-button>
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="4"><x-empty-state compact title="No bids" /></x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>
        </div>
    </x-page-shell>
</div>
