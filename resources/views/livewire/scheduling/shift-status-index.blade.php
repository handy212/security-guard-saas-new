<div>
    <x-page-shell title="Confirmations" description="Track assignment status and guard shift confirmations.">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('schedules.index')">Day roster</x-button>
            <x-button variant="secondary" :href="route('schedules.deployment-sheet')">Deployment sheet</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-schedules-nav /></x-slot:sidebar>

            <x-flash-status />

            <div class="stat-grid">
                <x-stat-card compact label="Awaiting confirm" :value="$pendingConfirmationCount" icon="pause" :tone="$pendingConfirmationCount ? 'warning' : 'success'" />
                <x-stat-card compact label="Confirmations" :value="$confirmations->total()" icon="check" tone="info" />
                <x-stat-card compact label="Assignments shown" :value="$assignments->count()" icon="guards" />
            </div>

            <div class="page-grid-2">
                <div class="space-y-3">
                    <x-page-toolbar>
                        <x-slot:tabs>
                            <x-segment-control field="confirmationFilter" :active="$confirmationFilter" :options="$confirmationStatuses" />
                        </x-slot:tabs>
                    </x-page-toolbar>
                    <x-section-card
                        title="Shift confirmations"
                        :description="$pendingConfirmationCount ? $pendingConfirmationCount.' awaiting response' : 'All caught up'"
                        flush
                    >
                        @forelse($confirmations as $confirmation)
                            <div class="list-row-start" wire:key="conf-{{ $confirmation->id }}">
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $confirmation->assignedGuard?->full_name }}</div>
                                    <div class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                        {{ $confirmation->shiftAssignment?->shift?->starts_at?->format('D, M j · H:i') }}
                                        · {{ $confirmation->shiftAssignment?->shift?->site?->name }}
                                    </div>
                                    @if ($confirmation->shiftAssignment?->shift?->title)
                                        <div class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $confirmation->shiftAssignment->shift->title }}</div>
                                    @endif
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <x-badge :status="$confirmation->status" />
                                    @if(\App\Support\EnumHelper::is($confirmation->status, 'pending'))
                                        <x-button size="sm" wire:click="confirmShift({{ $confirmation->id }})" wire:loading.attr="disabled" wire:target="confirmShift({{ $confirmation->id }})">
                                            <span wire:loading.remove wire:target="confirmShift({{ $confirmation->id }})">Confirm</span>
                                            <span wire:loading wire:target="confirmShift({{ $confirmation->id }})">…</span>
                                        </x-button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-3">
                                <x-empty-state compact title="No confirmations" description="Confirmations are created when guards are assigned to shifts." />
                            </div>
                        @endforelse
                        <div class="border-t border-zinc-100 px-4 py-2 dark:border-zinc-800">
                            <x-pagination :paginator="$confirmations" />
                        </div>
                    </x-section-card>
                </div>

                <div class="space-y-3">
                    <x-page-toolbar>
                        <x-slot:tabs>
                            <x-segment-control field="assignmentFilter" :active="$assignmentFilter" :options="$assignmentStatuses->all()" />
                        </x-slot:tabs>
                    </x-page-toolbar>
                    <x-section-card title="Recent & upcoming assignments" description="Last 7 days and future shifts." flush>
                        @forelse($assignments as $assignment)
                            <div class="list-row-start" wire:key="asg-{{ $assignment->id }}">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $assignment->assignedGuard?->full_name }}</span>
                                        <x-badge :status="$assignment->status" />
                                    </div>
                                    <div class="mt-0.5 text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                        {{ $assignment->shift?->title }}
                                        · {{ $assignment->shift?->starts_at?->format('D, M j · H:i') }}
                                        · {{ $assignment->shift?->site?->name }}
                                    </div>
                                </div>
                                <div class="table-inline-actions shrink-0">
                                    <x-button size="sm" variant="secondary" href="{{ route('schedules.index', ['date' => $assignment->shift?->starts_at?->toDateString()]) }}">View</x-button>
                                    <button type="button" wire:click="unassignGuard({{ $assignment->id }})" wire:confirm="Remove this guard from the shift?" class="table-action-danger">Unassign</button>
                                </div>
                            </div>
                        @empty
                            <div class="p-3">
                                <x-empty-state compact title="No assignments" description="Assignments from the last week and upcoming shifts appear here." />
                            </div>
                        @endforelse
                    </x-section-card>
                </div>
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
