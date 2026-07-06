<div>
    <x-page-shell title="Shift Status" description="Track assignment status and guard shift confirmations.">
        <x-schedules-nav />
        <x-flash-status />

        <div class="grid gap-4 lg:grid-cols-2">
            <div>
                <x-page-toolbar>
                    <x-slot:tabs>
                        <x-segment-control field="confirmationFilter" :active="$confirmationFilter" :options="$confirmationStatuses" />
                    </x-slot:tabs>
                </x-page-toolbar>
                <x-section-card title="Shift confirmations" :description="$pendingConfirmationCount ? $pendingConfirmationCount.' awaiting response' : null">
                    @forelse($confirmations as $confirmation)
                        <div class="flex items-center justify-between border-t border-zinc-100 py-2 text-sm first:border-0" wire:key="conf-{{ $confirmation->id }}">
                            <div>
                                <div class="font-medium">{{ $confirmation->assignedGuard?->full_name }}</div>
                                <div class="text-xs text-zinc-500">{{ $confirmation->shiftAssignment?->shift?->starts_at?->format('M j, H:i') }} · {{ $confirmation->shiftAssignment?->shift?->site?->name }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <x-badge :status="$confirmation->status" />
                                @if(\App\Support\EnumHelper::is($confirmation->status, 'pending'))
                                    <x-button size="sm" wire:click="confirmShift({{ $confirmation->id }})">Confirm</x-button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <x-empty-state compact title="No confirmations" description="Confirmations are created when guards are assigned to shifts." />
                    @endforelse
                    <x-pagination :paginator="$confirmations" />
                </x-section-card>
            </div>

            <div>
                <x-page-toolbar>
                    <x-slot:tabs>
                        <x-segment-control field="assignmentFilter" :active="$assignmentFilter" :options="$assignmentStatuses->all()" />
                    </x-slot:tabs>
                </x-page-toolbar>
                <x-section-card title="Recent & upcoming assignments" description="Last 7 days and future shifts.">
                    @forelse($assignments as $assignment)
                        <div class="flex items-center justify-between border-t border-zinc-100 py-2 text-sm first:border-0" wire:key="asg-{{ $assignment->id }}">
                            <div>
                                <div class="font-medium">{{ $assignment->assignedGuard?->full_name }}</div>
                                <div class="text-xs text-zinc-500">
                                    {{ $assignment->shift?->title }} · {{ $assignment->shift?->starts_at?->format('M j, H:i') }}
                                    · {{ $assignment->shift?->site?->name }}
                                </div>
                                <x-badge :status="$assignment->status" class="mt-1" />
                            </div>
                            <div class="flex shrink-0 gap-1">
                                <x-button size="sm" variant="secondary" href="{{ route('schedules.index', ['date' => $assignment->shift?->starts_at?->toDateString()]) }}">View</x-button>
                                <x-button size="sm" variant="secondary" wire:click="unassignGuard({{ $assignment->id }})" wire:confirm="Remove this guard from the shift?">Unassign</x-button>
                            </div>
                        </div>
                    @empty
                        <x-empty-state compact title="No assignments" />
                    @endforelse
                </x-section-card>
            </div>
        </div>
    </x-page-shell>
</div>
