<div>
    @php $pending = $leaveRequests->where('status', 'pending')->count(); @endphp

    <x-page-shell title="Time Off" description="Leave requests and guard availability.">
        <x-schedules-nav />
        <x-flash-status />

        <div class="stat-grid">
            <x-stat-card compact label="Requests" :value="$leaveRequests->count()" icon="users" />
            <x-stat-card compact label="Pending" :value="$pending" icon="pause" :tone="$pending ? 'warning' : 'success'" />
            <x-stat-card compact label="Availability rows" :value="$availabilities->count()" icon="schedules" tone="info" />
        </div>

        <x-form-card title="Submit time off request">
            <div class="grid gap-3 sm:grid-cols-2">
                <x-select wire:model="leaveForm.guard_id" label="Guard *">
                    <option value="">Select</option>
                    @foreach($guards as $g)<option value="{{ $g->id }}">{{ $g->full_name }}</option>@endforeach
                </x-select>
                <x-input wire:model="leaveForm.starts_on" type="date" label="Starts *" />
                <x-input wire:model="leaveForm.ends_on" type="date" label="Ends *" />
                <x-textarea wire:model="leaveForm.reason" label="Reason" class="sm:col-span-2" />
            </div>
            <x-button class="mt-3" wire:click="submitLeave">Submit request</x-button>
        </x-form-card>

        <x-section-card title="Leave requests" class="mt-4">
            @forelse($leaveRequests as $req)
                <div class="flex items-center justify-between border-t py-2 text-sm first:border-0" wire:key="leave-{{ $req->id }}">
                    <div>
                        <span class="font-medium">{{ $req->assignedGuard?->full_name }}</span>
                        <span class="text-zinc-500"> — {{ $req->starts_on?->format('M j') }} to {{ $req->ends_on?->format('M j') }}</span>
                        <x-badge :status="$req->status" class="ml-1" />
                    </div>
                    @if($req->status === 'pending')
                        <div class="flex gap-1">
                            <x-button size="sm" wire:click="approveLeave({{ $req->id }})">Approve</x-button>
                            <x-button size="sm" variant="secondary" wire:click="rejectLeave({{ $req->id }})">Reject</x-button>
                        </div>
                    @endif
                </div>
            @empty
                <x-empty-state compact title="No time off requests" />
            @endforelse
        </x-section-card>

        <x-section-card title="Guard availability" class="mt-4">
            @forelse($availabilities as $a)
                <div class="border-t py-1 text-sm first:border-0">{{ $a->assignedGuard?->full_name }} — {{ $a->weekday }} {{ $a->starts_at }}-{{ $a->ends_at }}</div>
            @empty
                <x-empty-state compact title="No availability on file" />
            @endforelse
        </x-section-card>
    </x-page-shell>
</div>
