<div>
    @php
        $active = $vehiclePatrols->filter(fn ($p) => $p->isActive())->count();
        $completed = $vehiclePatrols->filter(fn ($p) => ! $p->isActive())->count();
    @endphp

    <x-page-shell
        title="Vehicle Patrols"
        description="Assign fleet vehicles/motors to active patrols and log odometer + fuel."
        :breadcrumbs="[
            ['label' => 'Patrols', 'href' => route('patrols.index')],
            ['label' => 'Vehicle patrols'],
        ]"
    >
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('patrols.fleet') }}">Manage fleet</x-button>
            <x-button variant="secondary" href="{{ route('patrols.index') }}">Patrol board</x-button>
            <x-button wire:click="openStartForm">Start trip</x-button>
        </x-slot:actions>

        <x-flash-status />

        <div class="stat-grid">
            <x-stat-card compact label="Recent trips" :value="$vehiclePatrols->count()" icon="patrols" />
            <x-stat-card compact label="Active" :value="$active" icon="gps" tone="info" />
            <x-stat-card compact label="Completed" :value="$completed" icon="check" tone="success" />
            <x-stat-card compact label="Fleet available" :value="$availableFleet->count()" icon="schedules" />
        </div>

        <x-section-card title="Recent vehicle patrols" flush>
            <x-data-table>
                <x-table.head>
                    <tr>
                        <x-table.th>Vehicle</x-table.th>
                        <x-table.th>Driver</x-table.th>
                        <x-table.th responsive="md">Session</x-table.th>
                        <x-table.th>Odometer</x-table.th>
                        <x-table.th>Status</x-table.th>
                        <x-table.th align="right">Actions</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse($vehiclePatrols as $patrol)
                        <tr class="table-row-hover" wire:key="vpatrol-{{ $patrol->id }}">
                            <x-table.td>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $patrol->vehicle?->plate_number ?? $patrol->vehicle_number }}</span>
                                @if ($patrol->vehicle)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $patrol->vehicle->type->label() }}</div>
                                @endif
                            </x-table.td>
                            <x-table.td muted>{{ $patrol->assignedGuard?->full_name ?? ($patrol->driver_name ?: '—') }}</x-table.td>
                            <x-table.td responsive="md" muted>{{ $patrol->patrolSession?->route?->name ?? ($patrol->patrol_session_id ? '#'.$patrol->patrol_session_id : '—') }}</x-table.td>
                            <x-table.td muted class="tabular-nums">{{ $patrol->start_odometer ?? '—' }} → {{ $patrol->end_odometer ?? '…' }}</x-table.td>
                            <x-table.td><x-badge :status="$patrol->isActive() ? 'active' : 'completed'" /></x-table.td>
                            <x-table.td align="right">
                                @if ($patrol->isActive())
                                    <button type="button" class="table-action" wire:click="openEnd({{ $patrol->id }})">End trip</button>
                                @else
                                    <span class="text-xs tabular-nums text-zinc-400">{{ $patrol->ended_at?->format('M j, H:i') ?? $patrol->created_at?->format('M j, H:i') }}</span>
                                @endif
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="6">
                            <x-empty-state title="No vehicle patrols" description="Assign a fleet vehicle to start a trip.">
                                <x-slot:actions>
                                    <x-button size="sm" wire:click="openStartForm">Start trip</x-button>
                                    <x-button size="sm" variant="secondary" href="{{ route('patrols.fleet') }}">Manage fleet</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>
        </x-section-card>
    </x-page-shell>

    @if ($showStartForm)
        <x-drawer title="Start trip" description="Pick a fleet unit, optional driver, and in-progress patrol session." width="lg" close-method="closeStartForm">
            <x-drawer-form wire:submit.prevent="startTrip" submit-label="Start trip" close-method="closeStartForm" target="startTrip">
                <x-form-section title="Assignment">
                    <x-select wire:model="form.vehicle_id" label="Vehicle / motor *" class="sm:col-span-2">
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
                </x-form-section>
                <x-form-section title="Trip meters">
                    <x-input wire:model="form.start_odometer" label="Start odometer" type="number" hint="Uses fleet odometer if blank" />
                    <x-input wire:model="form.fuel_litres" label="Fuel (litres)" type="number" step="0.1" />
                    <x-input wire:model="form.fuel_cost" label="Fuel cost" type="number" step="0.01" class="sm:col-span-2" />
                </x-form-section>
                @if ($availableFleet->isEmpty())
                    <p class="sm:col-span-2 text-xs text-amber-700 dark:text-amber-400">
                        No available fleet units.
                        <a href="{{ route('patrols.fleet') }}" class="page-link">Add or free a vehicle</a>.
                    </p>
                @endif
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($endingId)
        <x-drawer title="End trip" description="Record end odometer and optional fuel before closing the trip." width="md" close-method="closeEndForm">
            <x-drawer-form wire:submit.prevent="endTrip" submit-label="Complete trip" close-method="closeEndForm" target="endTrip">
                <x-form-section title="Close out">
                    <x-input wire:model="endForm.end_odometer" label="End odometer *" type="number" class="sm:col-span-2" />
                    <x-select wire:model="endForm.patrol_session_id" label="Link session" class="sm:col-span-2">
                        <option value="">None</option>
                        @foreach ($sessions as $session)
                            <option value="{{ $session->id }}">#{{ $session->id }} · {{ $session->route?->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="endForm.fuel_litres" label="Fuel (litres)" type="number" step="0.1" />
                    <x-input wire:model="endForm.fuel_cost" label="Fuel cost" type="number" step="0.01" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
