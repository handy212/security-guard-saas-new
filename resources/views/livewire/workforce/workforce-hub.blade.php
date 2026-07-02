<div>
    <x-page-shell title="Workforce" description="Time off, guard availability, and shift confirmations.">
        @php
            $pendingLeave = $leaveRequests->where('status', 'pending')->count();
            $pendingConfirm = $confirmations->where('status', 'pending')->count();
        @endphp

        <div class="stat-grid">
            <x-stat-card compact label="Leave requests" :value="$leaveRequests->count()" icon="users" />
            <x-stat-card compact label="Pending leave" :value="$pendingLeave" icon="pause" :tone="$pendingLeave ? 'warning' : 'success'" />
            <x-stat-card compact label="Availability" :value="$availabilities->count()" icon="schedules" tone="info" />
            <x-stat-card compact label="Confirmations" :value="$pendingConfirm" icon="check" :tone="$pendingConfirm ? 'warning' : 'success'" />
        </div>

        <x-page-toolbar>
            <x-slot:tabs>
                <x-segment-control model="tab" :active="$tab" :options="['timeoff' => 'Time off', 'availability' => 'Availability', 'confirmations' => 'Shift confirm']" />
            </x-slot:tabs>
        </x-page-toolbar>

        @if($tab === 'timeoff')
            <x-form-card title="Submit leave request">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-select wire:model="leaveForm.guard_id" label="Guard">
                        <option value="">Select</option>
                        @foreach($guards as $g)<option value="{{ $g->id }}">{{ $g->full_name }}</option>@endforeach
                    </x-select>
                    <x-input wire:model="leaveForm.starts_on" type="date" label="Starts" />
                    <x-input wire:model="leaveForm.ends_on" type="date" label="Ends" />
                    <x-textarea wire:model="leaveForm.reason" label="Reason" />
                </div>
                <x-button class="mt-3" wire:click="submitLeave">Submit</x-button>
            </x-form-card>
            <x-section-card title="Pending requests" class="mt-4">
                @forelse($leaveRequests as $req)
                    <div class="flex justify-between border-t py-2 first:border-0 text-sm" wire:key="leave-{{ $req->id }}">
                        <span>{{ $req->assignedGuard?->full_name }} — {{ $req->starts_on?->format('M j') }} to {{ $req->ends_on?->format('M j') }} <x-badge :status="$req->status" class="ml-1" /></span>
                        @if($req->status === 'pending')
                            <x-button size="sm" wire:click="approveLeave({{ $req->id }})">Approve</x-button>
                        @endif
                    </div>
                @empty
                    <x-empty-state compact title="No leave requests" />
                @endforelse
            </x-section-card>
        @endif

        @if($tab === 'availability')
            <x-form-card title="Set weekly availability (guard self-service)">
                <div class="grid gap-3 sm:grid-cols-4">
                    <x-select wire:model="availabilityForm.day_of_week" label="Day">
                        @foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d => $label)
                            <option value="{{ $d }}">{{ $label }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="availabilityForm.start_time" type="time" label="From" />
                    <x-input wire:model="availabilityForm.end_time" type="time" label="To" />
                    <div class="flex items-end"><x-button wire:click="saveAvailability">Save</x-button></div>
                </div>
            </x-form-card>
            <x-section-card title="All availability" class="mt-4">
                @forelse($availabilities as $a)
                    <div class="border-t py-1 text-sm first:border-0" wire:key="avail-{{ $a->id }}">{{ $a->assignedGuard?->full_name }} — {{ $a->weekday }} {{ $a->starts_at }}-{{ $a->ends_at }}</div>
                @empty
                    <x-empty-state title="No availability set" />
                @endforelse
            </x-section-card>
        @endif

        @if($tab === 'confirmations')
            <x-section-card title="Shift confirmations">
                @forelse($confirmations as $c)
                    <div class="flex justify-between border-t py-2 first:border-0 text-sm" wire:key="confirm-{{ $c->id }}">
                        <span>{{ $c->assignedGuard?->full_name }} — {{ $c->shiftAssignment?->shift?->starts_at?->format('M j H:i') }} <x-badge :status="$c->status" class="ml-1" /></span>
                        @if($c->status === 'pending')
                            <x-button size="sm" wire:click="confirmShift({{ $c->id }})">Confirm</x-button>
                        @endif
                    </div>
                @empty
                    <x-empty-state title="No pending confirmations" />
                @endforelse
            </x-section-card>
        @endif
    </x-page-shell>
</div>
