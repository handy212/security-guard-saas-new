<div>
    <x-page-shell title="Schedule" description="Create shifts, assign guards, and manage daily deployments.">
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('schedules.calendar') }}">Calendar</x-button>
            <x-button variant="secondary" href="{{ route('schedules.deployment-sheet') }}">Deployment sheet</x-button>
            <x-button wire:click="openForm">Create shift</x-button>
        </x-slot:actions>

        <x-schedules-nav />
        <x-flash-status />

        <div class="stat-grid">
            <x-stat-card compact label="Shifts today" :value="$stats['shifts_today']" icon="schedules" />
            <x-stat-card compact label="Open shifts" :value="$stats['open_shifts']" icon="pause" :tone="$stats['open_shifts'] ? 'warning' : 'success'" />
            <x-stat-card compact label="Staffed" :value="$stats['staffed']" icon="check" tone="success" />
            <x-stat-card compact label="On duty" :value="$stats['on_duty']" icon="guards" tone="info" />
        </div>

        <x-page-toolbar>
            <x-slot:controls>
                <x-input wire:model.live="date" type="date" label="Date" class="w-auto text-sm" />
            </x-slot:controls>
        </x-page-toolbar>

        <div class="grid gap-3">
            @forelse($shifts as $shift)
                <div class="card-surface p-3" wire:key="shift-{{ $shift->id }}">
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

                    @if($shift->assignments->count() < $shift->required_guards)
                        <div class="mt-3 flex flex-col gap-2 border-t border-zinc-100 pt-3 sm:flex-row sm:items-end">
                            <x-select wire:model="pendingGuard.{{ $shift->id }}" label="Assign guard" class="sm:max-w-xs">
                                <option value="">Select guard</option>
                                @foreach ($guards as $guard)
                                    <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                                @endforeach
                            </x-select>
                            <x-button type="button" size="sm" wire:click="assignGuard({{ $shift->id }})">Assign</x-button>
                            <x-button type="button" size="sm" variant="secondary" wire:click="postOpen({{ $shift->id }})">Post open</x-button>
                        </div>
                        @if ($guards->isEmpty())
                            <p class="mt-2 text-xs text-amber-700">
                                No verified guards available.
                                @if ($unverifiedGuardCount > 0)
                                    <a href="{{ route('guards.kyg') }}" class="font-medium underline">Complete Know Your Guard vetting</a> for {{ $unverifiedGuardCount }} pending officer{{ $unverifiedGuardCount === 1 ? '' : 's' }}.
                                @endif
                            </p>
                        @endif
                    @endif
                </div>
            @empty
                <x-empty-state title="No shifts" description="Create a shift or pick another date." />
            @endforelse
        </div>
    </x-page-shell>

    @if ($showForm)
        <x-drawer title="Create shift" width="lg">
            <x-drawer-form wire:submit.prevent="save" submit-label="Create shift">
                <x-select wire:model="form.client_account_id" label="Client *">
                    <option value="">Client</option>
                    @foreach ($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </x-select>
                <x-select wire:model="form.site_id" label="Site *">
                    <option value="">Site</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.title" label="Title *" class="sm:col-span-2" />
                <x-input wire:model="form.starts_at" label="Starts *" type="datetime-local" />
                <x-input wire:model="form.ends_at" label="Ends *" type="datetime-local" />
                <x-input wire:model="form.required_guards" label="Required guards" type="number" min="1" />
                <x-input wire:model="form.billing_rate" label="Billing rate" type="number" step="0.01" />
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
