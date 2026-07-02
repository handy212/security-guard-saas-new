<div>
    <x-page-shell title="Shift Status" description="Track assignment status and guard shift confirmations.">
        <x-schedules-nav />
        <x-flash-status />

        <x-page-toolbar>
            <x-slot:tabs>
                <x-segment-control model="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed', 'assigned' => 'Assigned']" />
            </x-slot:tabs>
        </x-page-toolbar>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-section-card title="Shift confirmations">
                @forelse($confirmations as $confirmation)
                    <div class="flex items-center justify-between border-t py-2 text-sm first:border-0" wire:key="conf-{{ $confirmation->id }}">
                        <div>
                            <div class="font-medium">{{ $confirmation->assignedGuard?->full_name }}</div>
                            <div class="text-xs text-zinc-500">{{ $confirmation->shiftAssignment?->shift?->starts_at?->format('M j, H:i') }} · {{ $confirmation->shiftAssignment?->shift?->site?->name }}</div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-badge :status="$confirmation->status" />
                            @if($confirmation->status === 'pending')
                                <x-button size="sm" wire:click="confirmShift({{ $confirmation->id }})">Confirm</x-button>
                            @endif
                        </div>
                    </div>
                @empty
                    <x-empty-state compact title="No confirmations" />
                @endforelse
                <x-pagination :paginator="$confirmations" />
            </x-section-card>

            <x-section-card title="Recent assignments">
                @forelse($assignments as $assignment)
                    <div class="border-t py-2 text-sm first:border-0" wire:key="asg-{{ $assignment->id }}">
                        <div class="font-medium">{{ $assignment->assignedGuard?->full_name }}</div>
                        <div class="text-xs text-zinc-500">{{ $assignment->shift?->title }} · <x-badge :status="$assignment->status" /></div>
                    </div>
                @empty
                    <x-empty-state compact title="No assignments" />
                @endforelse
            </x-section-card>
        </div>
    </x-page-shell>
</div>
