<div>
    <x-page-shell title="Open Shifts" description="Unfilled shifts and guard bids.">
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>

        <x-flash-status />

        <div class="page-grid-2">
            <x-section-card title="Shifts needing guards" :description="$openShifts->isEmpty() ? 'All upcoming shifts are fully staffed.' : null">
                @forelse($openShifts as $shift)
                    <div class="border-t border-zinc-100 py-2 text-sm first:border-0" wire:key="open-{{ $shift->id }}">
                        <div class="font-medium text-zinc-900">{{ $shift->title }}</div>
                        <div class="text-xs text-zinc-500">{{ $shift->site?->name }} · {{ $shift->starts_at?->format('M j, H:i') }}</div>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                            <span class="text-amber-700">{{ $shift->activeAssignmentsCount() }}/{{ $shift->required_guards }} filled</span>
                            <x-badge :status="$shift->status" />
                            <a href="{{ route('schedules.index') }}" class="font-medium text-zinc-600 hover:underline">Assign from schedule</a>
                        </div>
                    </div>
                @empty
                    <x-empty-state compact title="All shifts staffed" />
                @endforelse
            </x-section-card>

            <x-section-card title="Guard bids" :description="$pendingBidCount ? $pendingBidCount.' pending review' : 'No pending bids'">
                <x-page-toolbar class="mb-3 !border-0 !p-0 !shadow-none">
                    <x-slot:tabs>
                        <x-segment-control field="bidFilter" :active="$bidFilter" :options="['pending' => 'Pending', 'all' => 'All', 'approved' => 'Approved', 'rejected' => 'Rejected']" />
                    </x-slot:tabs>
                </x-page-toolbar>
                <x-data-table>
                    <x-table.head><tr><x-table.th>Guard</x-table.th><x-table.th>Shift</x-table.th><x-table.th>Notes</x-table.th><x-table.th>Status</x-table.th><x-table.th align="right">Actions</x-table.th></tr></x-table.head>
                    <tbody>
                        @forelse($bids as $bid)
                            <tr wire:key="bid-{{ $bid->id }}">
                                <x-table.td>{{ $bid->assignedGuard?->full_name }}</x-table.td>
                                <x-table.td muted>{{ $bid->shift?->title }}</x-table.td>
                                <x-table.td muted>{{ $bid->notes ?: '—' }}</x-table.td>
                                <x-table.td><x-badge :status="$bid->status" /></x-table.td>
                                <x-table.td align="right">
                                    @if(\App\Support\EnumHelper::is($bid->status, 'pending'))
                                        <div class="flex justify-end gap-1">
                                            <x-button size="sm" wire:click="approveBid({{ $bid->id }})">Approve</x-button>
                                            <x-button size="sm" variant="secondary" wire:click="rejectBid({{ $bid->id }})">Reject</x-button>
                                        </div>
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="5"><x-empty-state compact title="No bids" description="Guards can bid via the mobile app when shifts are posted open." /></x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>
        </div>
            </x-sub-sidebar-layout>
    </x-page-shell>
</div>
