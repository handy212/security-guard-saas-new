<div>
    @php
        $active = $vehiclePatrols->filter(fn ($p) => $p->isActive())->count();
        $completed = $vehiclePatrols->filter(fn ($p) => ! $p->isActive())->count();
    @endphp

    <x-page-shell title="Vehicle Patrols" description="Assign fleet vehicles/motors to active patrols and log odometer + fuel.">
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('patrols.fleet') }}">Manage fleet</x-button>
            <x-button variant="secondary" href="{{ route('patrols.index') }}">Patrol board</x-button>
        </x-slot:actions>

        <x-flash-status />

        <div class="stat-grid">
            <x-stat-card compact label="Recent trips" :value="$vehiclePatrols->count()" icon="patrols" />
            <x-stat-card compact label="Active" :value="$active" icon="gps" tone="info" />
            <x-stat-card compact label="Completed" :value="$completed" icon="check" tone="success" />
            <x-stat-card compact label="Fleet available" :value="$availableFleet->count()" icon="schedules" />
        </div>

        <x-form-card title="Assign vehicle to patrol" description="Pick a fleet unit, optional driver and in-progress patrol session." collapsible open>
            <form wire:submit="startTrip" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <x-select wire:model="form.vehicle_id" label="Vehicle / motor" required>
                    <option value="">Select vehicle</option>
                    @foreach ($availableFleet as $vehicle)
                        <option value="{{ $vehicle->id }}">
                            {{ $vehicle->plate_number }} · {{ $vehicle->type->label() }}
                            @if ($vehicle->name) — {{ $vehicle->name }} @endif
                        </option>
                    @endforeach
                </x-select>
                <x-select wire:model="form.guard_id" label="Driver (guard)">
                    <option value="">Optional</option>
                    @foreach ($guards as $guard)
                        <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                    @endforeach
                </x-select>
                <x-select wire:model="form.patrol_session_id" label="Patrol session">
                    <option value="">None yet</option>
                    @foreach ($sessions as $session)
                        <option value="{{ $session->id }}">
                            #{{ $session->id }} · {{ $session->route?->name ?? 'Route' }} · {{ $session->status }}
                        </option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.start_odometer" label="Start odometer" type="number" placeholder="Uses fleet odometer if blank" />
                <x-input wire:model="form.fuel_litres" label="Fuel (litres)" type="number" step="0.1" />
                <x-input wire:model="form.fuel_cost" label="Fuel cost" type="number" step="0.01" />
                <div class="md:col-span-2 xl:col-span-3">
                    <x-button type="submit">Start trip</x-button>
                    @if ($availableFleet->isEmpty())
                        <p class="mt-2 text-xs text-amber-700">No available fleet units. <a href="{{ route('patrols.fleet') }}" class="font-medium underline">Add or free a vehicle</a>.</p>
                    @endif
                </div>
            </form>
        </x-form-card>

        @if ($endingId)
            <x-form-card title="End trip" collapsible open>
                <form wire:submit="endTrip" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <x-input wire:model="endForm.end_odometer" label="End odometer" type="number" required />
                    <x-select wire:model="endForm.patrol_session_id" label="Link session">
                        <option value="">None</option>
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}">#{{ $session->id }} · {{ $session->route?->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="endForm.fuel_litres" label="Fuel (litres)" type="number" step="0.1" />
                    <x-input wire:model="endForm.fuel_cost" label="Fuel cost" type="number" step="0.01" />
                    <div class="flex gap-2 md:col-span-2 xl:col-span-4">
                        <x-button type="submit">Complete trip</x-button>
                        <x-button type="button" variant="secondary" wire:click="$set('endingId', null)">Cancel</x-button>
                    </div>
                </form>
            </x-form-card>
        @endif

        <x-data-table title="Recent vehicle patrols">
            <x-table.head>
                <tr>
                    <x-table.th>Vehicle</x-table.th>
                    <x-table.th>Driver</x-table.th>
                    <x-table.th responsive="md">Session</x-table.th>
                    <x-table.th>Odometer</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th>Actions</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($vehiclePatrols as $patrol)
                    <tr class="table-row-hover" wire:key="vpatrol-{{ $patrol->id }}">
                        <x-table.td>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $patrol->vehicle?->plate_number ?? $patrol->vehicle_number }}</span>
                            @if ($patrol->vehicle)
                                <div class="text-xs text-zinc-500">{{ $patrol->vehicle->type->label() }}</div>
                            @endif
                        </x-table.td>
                        <x-table.td muted>{{ $patrol->assignedGuard?->full_name ?? ($patrol->driver_name ?: '—') }}</x-table.td>
                        <x-table.td responsive="md" muted>{{ $patrol->patrolSession?->route?->name ?? ($patrol->patrol_session_id ? '#'.$patrol->patrol_session_id : '—') }}</x-table.td>
                        <x-table.td muted>{{ $patrol->start_odometer ?? '—' }} → {{ $patrol->end_odometer ?? '…' }}</x-table.td>
                        <x-table.td><x-badge :status="$patrol->isActive() ? 'active' : 'completed'" /></x-table.td>
                        <x-table.td>
                            @if ($patrol->isActive())
                                <button type="button" class="text-xs font-medium text-accent-600 hover:underline" wire:click="openEnd({{ $patrol->id }})">End trip</button>
                            @else
                                <span class="text-xs text-zinc-400">{{ $patrol->ended_at?->format('M j, H:i') ?? $patrol->created_at?->format('M j, H:i') }}</span>
                            @endif
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="6"><x-empty-state title="No vehicle patrols" description="Assign a fleet vehicle to start a trip." /></x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>
    </x-page-shell>
</div>
