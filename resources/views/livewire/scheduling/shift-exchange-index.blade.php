<div>
    <x-page-shell :embedded="$embedded" title="Shift Exchange" description="Review and approve guard shift swap requests.">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('schedules.index')">Day roster</x-button>
            <x-button variant="secondary" :href="route('schedules.shift-status')">Confirmations</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            @if (! $embedded)
                <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>
            @endif

            <x-flash-status />

            <div class="stat-grid">
                <x-stat-card compact label="Awaiting approval" :value="$pendingSwapCount" icon="pause" :tone="$pendingSwapCount ? 'warning' : 'success'" />
                <x-stat-card compact label="Shown" :value="$swaps->count()" icon="schedules" tone="info" />
            </div>

            <x-page-toolbar>
                <x-slot:tabs>
                    <x-segment-control field="swapFilter" :active="$swapFilter" :options="['pending' => 'Pending', 'all' => 'All', 'approved' => 'Approved', 'rejected' => 'Rejected']" />
                </x-slot:tabs>
                @if($pendingSwapCount)
                    <x-slot:controls>
                        <span class="status-chip status-chip-warning">
                            <span class="tabular-nums font-semibold">{{ $pendingSwapCount }}</span> awaiting approval
                        </span>
                    </x-slot:controls>
                @endif
            </x-page-toolbar>

            <x-section-card title="Swap requests" flush>
                <x-data-table class="scheduler-table">
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
                            <tr class="table-row-hover" wire:key="swap-{{ $swap->id }}">
                                <x-table.td data-label="Requested by" class="font-medium text-zinc-900 dark:text-zinc-100">{{ $swap->requestedByGuard?->full_name }}</x-table.td>
                                <x-table.td data-label="Shift">
                                    <div class="text-sm text-zinc-800 dark:text-zinc-200">{{ $swap->shiftAssignment?->shift?->title ?? '—' }}</div>
                                    <div class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                        {{ $swap->shiftAssignment?->shift?->site?->name }}
                                        @if ($swap->shiftAssignment?->shift?->starts_at)
                                            · {{ $swap->shiftAssignment->shift->starts_at->format('M j, H:i') }}
                                        @endif
                                    </div>
                                    @if ($swap->shiftAssignment?->shift?->starts_at)
                                        <a href="{{ route('schedules.index', ['date' => $swap->shiftAssignment->shift->starts_at->toDateString()]) }}" class="page-link mt-1 inline-block text-xs" wire:navigate>View day roster</a>
                                    @endif
                                </x-table.td>
                                <x-table.td data-label="Replacement" class="font-medium text-zinc-800 dark:text-zinc-200">{{ $swap->replacementGuard?->full_name ?? '—' }}</x-table.td>
                                <x-table.td data-label="Reason" muted>{{ $swap->reason ?: '—' }}</x-table.td>
                                <x-table.td data-label="Status">
                                    @if (\App\Support\EnumHelper::is($swap->status, 'pending') && ! $swap->shiftAssignment?->shift?->starts_at?->isFuture())
                                        <span class="status-chip status-chip-neutral">Shift started</span>
                                    @else
                                        <x-badge :status="$swap->status" />
                                    @endif
                                </x-table.td>
                                <x-table.td data-label="Actions" align="right">
                                    @if(\App\Support\EnumHelper::is($swap->status, 'pending') && $swap->shiftAssignment?->shift?->starts_at?->isFuture())
                                        <div class="table-inline-actions justify-end">
                                            <x-button size="sm" wire:click="approveSwap({{ $swap->id }})" wire:target="approveSwap({{ $swap->id }})" aria-label="Approve shift exchange for {{ $swap->requestedByGuard?->full_name ?? 'guard' }}">
                                                <span wire:loading.remove wire:target="approveSwap({{ $swap->id }})">Approve</span>
                                                <span wire:loading wire:target="approveSwap({{ $swap->id }})">…</span>
                                            </x-button>
                                            <x-button size="sm" variant="secondary" wire:click="rejectSwap({{ $swap->id }})" wire:target="rejectSwap({{ $swap->id }})" aria-label="Reject shift exchange for {{ $swap->requestedByGuard?->full_name ?? 'guard' }}">Reject</x-button>
                                        </div>
                                    @elseif(\App\Support\EnumHelper::is($swap->status, 'pending'))
                                        <span class="text-xs text-zinc-400">No action available</span>
                                    @endif
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="6">
                                <x-empty-state class="scheduler-empty-state"
                                    title="No shift exchanges"
                                    description="When guards request a swap from the field app, it appears here for approval."
                                />
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
