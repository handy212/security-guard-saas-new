<div>
    <x-page-shell title="Scheduling & Rostering" description="Create shifts, assign guards, and manage deployments.">
        <x-slot:actions>
            <a href="{{ route('schedules.calendar') }}" class="btn-secondary">Calendar</a>
            <x-button wire:click="openForm">Create shift</x-button>
        </x-slot:actions>

        <x-stat-grid>
            <x-stat-card compact label="Shifts" :value="$scheduleStats['total']" icon="shifts" />
            <x-stat-card compact label="Open" :value="$scheduleStats['open']" icon="pause" :tone="$scheduleStats['open'] > 0 ? 'warning' : 'default'" />
            <x-stat-card compact label="Staffed" :value="$scheduleStats['staffed']" icon="check" tone="success" />
            <x-stat-card compact label="Need guards" :value="$scheduleStats['needs_guards']" icon="guards" :tone="$scheduleStats['needs_guards'] > 0 ? 'info' : 'default'" />
        </x-stat-grid>

        <x-page-toolbar>
            <x-slot:controls>
                <label class="flex items-center gap-2 text-sm text-zinc-600">
                    <span>Date</span>
                    <input wire:model.live="date" type="date" class="form-input !w-auto text-sm" />
                </label>
            </x-slot:controls>
        </x-page-toolbar>

        <div class="grid gap-3">
            @forelse($shifts as $shift)
                <div class="rounded-lg border border-zinc-200 bg-white p-3" wire:key="shift-{{ $shift->id }}">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-semibold">{{ $shift->title }}</h3>
                                <x-badge :status="$shift->status" />
                            </div>
                            <p class="text-xs text-zinc-500">
                                {{ $shift->site?->name }} · {{ $shift->starts_at?->format('M j, H:i') }} – {{ $shift->ends_at?->format('H:i') }}
                            </p>
                        </div>
                        <div @class([
                            'rounded-md px-2 py-1 text-xs font-medium',
                            'bg-emerald-50 text-emerald-800' => $shift->assignments->count() >= $shift->required_guards,
                            'bg-amber-50 text-amber-800' => $shift->assignments->count() < $shift->required_guards,
                        ])>
                            {{ $shift->assignments->count() }}/{{ $shift->required_guards }} assigned
                        </div>
                    </div>

                    @if ($shift->assignments->isNotEmpty())
                        <div class="mt-2 flex flex-wrap gap-1">
                            @foreach ($shift->assignments as $assignment)
                                <span class="rounded bg-zinc-100 px-2 py-0.5 text-xs">{{ $assignment->assignedGuard?->full_name ?? 'Guard' }}</span>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-3 flex flex-col gap-2 border-t border-zinc-100 pt-3 sm:flex-row sm:items-center">
                        <x-select wire:model="assignGuardId" class="sm:max-w-xs">
                            <option value="">Select guard</option>
                            @foreach ($guards as $guard)
                                <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                            @endforeach
                        </x-select>
                        <x-button type="button" variant="secondary" wire:click="$set('assignShiftId', {{ $shift->id }})" size="sm">Select shift</x-button>
                        <x-button type="button" wire:click="assign" size="sm">Assign</x-button>
                    </div>
                </div>
            @empty
                <x-empty-state title="No shifts" description="Create a shift or pick another date." />
            @endforelse
        </div>
    </x-page-shell>

    @if ($showForm)
        <x-drawer title="Create shift" width="lg">
            <form wire:submit="save" class="grid gap-3 sm:grid-cols-2">
                <x-select wire:model="form.client_account_id" label="Client">
                    <option value="">Client</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </x-select>
                <x-select wire:model="form.site_id" label="Site">
                    <option value="">Site</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.title" label="Title" class="sm:col-span-2" />
                <x-input wire:model="form.starts_at" label="Starts" type="datetime-local" />
                <x-input wire:model="form.ends_at" label="Ends" type="datetime-local" />
                <x-input wire:model="form.required_guards" label="Required guards" type="number" min="1" />
                <x-input wire:model="form.billing_rate" label="Billing rate" type="number" step="0.01" />
                <div class="flex gap-2 sm:col-span-2">
                    <x-button type="submit">Create</x-button>
                    <x-button type="button" variant="secondary" wire:click="closeDrawer">Cancel</x-button>
                </div>
            </form>
        </x-drawer>
    @endif
</div>
