<div>
    <x-page-shell title="Open Shifts" description="Unfilled shifts and guard bids waiting for review.">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('schedules.index')">Day roster</x-button>
            <x-button variant="secondary" :href="route('schedules.deploy')">Deploy wizard</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>

            <x-flash-status />

            <div class="stat-grid">
                <x-stat-card compact label="Open shifts" :value="$openShifts->count()" icon="pause" :tone="$openShifts->isNotEmpty() ? 'warning' : 'success'" />
                <x-stat-card compact label="Pending bids" :value="$pendingBidCount" icon="guards" :tone="$pendingBidCount ? 'warning' : 'default'" />
                <x-stat-card compact label="Bids shown" :value="$bids->count()" icon="schedules" tone="info" />
            </div>

            <div class="page-grid-2">
                <x-section-card
                    title="Shifts needing guards"
                    :description="$openShifts->isEmpty() ? 'All upcoming shifts are fully staffed.' : $openShifts->count().' underfilled'"
                    flush
                >
                    @forelse($openShifts as $shift)
                        @php
                            $filled = $shift->activeAssignmentsCount();
                            $short = max(0, $shift->required_guards - $filled);
                        @endphp
                        <div class="list-row-start" wire:key="open-{{ $shift->id }}">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $shift->title }}</span>
                                    <x-badge :status="$shift->status" />
                                </div>
                                <div class="mt-0.5 text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                    {{ $shift->site?->name }} · {{ $shift->starts_at?->format('D, M j · H:i') }}
                                </div>
                                <div class="mt-1.5 flex flex-wrap items-center gap-2">
                                    <span class="staffing-pill staffing-pill-low">{{ $filled }}/{{ $shift->required_guards }} filled</span>
                                    <span class="text-xs text-amber-700 dark:text-amber-400">Needs {{ $short }}</span>
                                </div>
                            </div>
                            <a href="{{ route('schedules.index', ['date' => $shift->starts_at?->toDateString()]) }}" class="table-action shrink-0" wire:navigate>
                                Assign →
                            </a>
                        </div>
                    @empty
                        <div class="p-3">
                            <x-empty-state
                                compact
                                title="All shifts staffed"
                                description="New gaps appear here when a shift is short of required guards."
                            >
                                <x-slot:actions>
                                    <x-button size="sm" variant="secondary" :href="route('schedules.index')">Day roster</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </div>
                    @endforelse
                </x-section-card>

                <x-section-card title="Guard bids" :description="$pendingBidCount ? $pendingBidCount.' pending review' : 'No pending bids'">
                    <x-page-toolbar class="mb-3 !border-0 !p-0 !shadow-none">
                        <x-slot:tabs>
                            <x-segment-control field="bidFilter" :active="$bidFilter" :options="['pending' => 'Pending', 'all' => 'All', 'approved' => 'Approved', 'rejected' => 'Rejected']" />
                        </x-slot:tabs>
                    </x-page-toolbar>
                    <x-data-table>
                        <x-table.head>
                            <tr>
                                <x-table.th>Guard</x-table.th>
                                <x-table.th>Shift</x-table.th>
                                <x-table.th>Notes</x-table.th>
                                <x-table.th>Status</x-table.th>
                                <x-table.th align="right">Actions</x-table.th>
                            </tr>
                        </x-table.head>
                        <tbody>
                            @forelse($bids as $bid)
                                <tr class="table-row-hover" wire:key="bid-{{ $bid->id }}">
                                    <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $bid->assignedGuard?->full_name }}</x-table.td>
                                    <x-table.td>
                                        <div class="text-sm text-zinc-800 dark:text-zinc-200">{{ $bid->shift?->title }}</div>
                                        <div class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                            {{ $bid->shift?->site?->name }}
                                            @if ($bid->shift?->starts_at)
                                                · {{ $bid->shift->starts_at->format('M j, H:i') }}
                                            @endif
                                        </div>
                                    </x-table.td>
                                    <x-table.td muted>{{ $bid->notes ?: '—' }}</x-table.td>
                                    <x-table.td><x-badge :status="$bid->status" /></x-table.td>
                                    <x-table.td align="right">
                                        @if(\App\Support\EnumHelper::is($bid->status, 'pending'))
                                            <div class="table-inline-actions justify-end">
                                                <x-button size="sm" wire:click="approveBid({{ $bid->id }})" wire:loading.attr="disabled" wire:target="approveBid({{ $bid->id }})">
                                                    <span wire:loading.remove wire:target="approveBid({{ $bid->id }})">Approve</span>
                                                    <span wire:loading wire:target="approveBid({{ $bid->id }})">…</span>
                                                </x-button>
                                                <x-button size="sm" variant="secondary" wire:click="rejectBid({{ $bid->id }})">Reject</x-button>
                                            </div>
                                        @endif
                                    </x-table.td>
                                </tr>
                            @empty
                                <x-table.empty colspan="5">
                                    <x-empty-state compact title="No bids" description="Guards can bid via the mobile app when shifts are posted open." />
                                </x-table.empty>
                            @endforelse
                        </tbody>
                    </x-data-table>
                </x-section-card>
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
