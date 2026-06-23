<div>
    <x-page-header title="Scheduling & Rostering" description="Create shifts, assign guards, and manage daily deployments.">
        <x-slot:actions>
            <label class="flex items-center gap-2 text-sm">
                <span class="text-slate-500">Date</span>
                <input wire:model.live="date" type="date" class="form-input !w-auto" />
            </label>
        </x-slot:actions>
    </x-page-header>

    <div class="space-y-5 p-6">
        <x-form-card title="Create shift" collapsible>
            <form wire:submit="save" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-select wire:model="form.client_account_id" label="Client">
                    <option value="">Client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </x-select>
                <x-select wire:model="form.site_id" label="Site">
                    <option value="">Site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.title" label="Shift title" class="md:col-span-2" />
                <x-input wire:model="form.starts_at" label="Starts" type="datetime-local" />
                <x-input wire:model="form.ends_at" label="Ends" type="datetime-local" />
                <x-input wire:model="form.required_guards" label="Required guards" type="number" min="1" />
                <x-input wire:model="form.billing_rate" label="Billing rate" type="number" step="0.01" />
                <div class="xl:col-span-4">
                    <x-button type="submit">Create shift</x-button>
                </div>
            </form>
        </x-form-card>

        <div class="grid gap-4">
            @forelse($shifts as $shift)
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-slate-900">{{ $shift->title }}</h3>
                                <x-badge :status="$shift->status" />
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $shift->site?->name }}
                                · {{ $shift->starts_at?->format('M j, H:i') }} – {{ $shift->ends_at?->format('H:i') }}
                            </p>
                        </div>
                        <div class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-semibold text-slate-700">
                            {{ $shift->assignments->count() }}/{{ $shift->required_guards }} assigned
                        </div>
                    </div>

                    @if($shift->assignments->isNotEmpty())
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($shift->assignments as $assignment)
                                <span class="inline-flex items-center gap-1 rounded-full bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-800">
                                    {{ $assignment->assignedGuard?->full_name ?? 'Guard #'.$assignment->guard_id }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-4 flex flex-col gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:items-center">
                        <x-select wire:model="assignGuardId" class="sm:max-w-xs">
                            <option value="">Select guard to assign</option>
                            @foreach($guards as $guard)
                                <option value="{{ $guard->id }}">{{ $guard->first_name }} {{ $guard->last_name }}</option>
                            @endforeach
                        </x-select>
                        <x-button type="button" variant="secondary" wire:click="$set('assignShiftId', {{ $shift->id }})" size="sm">Select shift</x-button>
                        <x-button type="button" wire:click="assign" size="sm">Assign guard</x-button>
                    </div>
                </div>
            @empty
                <x-empty-state title="No shifts for this date" description="Create a shift above or pick a different date." action="/schedules" actionLabel="Refresh" />
            @endforelse
        </div>
    </div>
</div>
