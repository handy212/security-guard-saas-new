<div>
    <x-page-shell
        title="Fleet"
        description="Register cars, motors, and vans. They sync into Assets so you can issue them on Deploy."
        :breadcrumbs="[
            ['label' => 'Patrols', 'href' => route('patrols.index')],
            ['label' => 'Fleet'],
        ]"
    >
        <x-slot:actions>
            <x-button variant="secondary" href="{{ route('assets.index') }}">Assets kit list</x-button>
            <x-button variant="secondary" href="{{ route('patrols.vehicles') }}">Vehicle patrols</x-button>
            <x-button wire:click="openCreate">Add vehicle</x-button>
        </x-slot:actions>

        <x-flash-status />

        <div class="stat-grid">
            <x-stat-card compact label="Fleet" :value="$stats['total']" icon="patrols" />
            <x-stat-card compact label="Available" :value="$stats['available']" icon="check" tone="success" />
            <x-stat-card compact label="In use" :value="$stats['in_use']" icon="gps" :tone="$stats['in_use'] ? 'warning' : 'default'" />
            <x-stat-card compact label="Motors" :value="$stats['motors']" icon="patrols" tone="info" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search plate, name, make…">
            <x-slot:controls>
                <x-filter-select wire:model.live="typeFilter">
                    <option value="all">All types</option>
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}">{{ $type->label() }}</option>
                    @endforeach
                </x-filter-select>
                <x-filter-select wire:model.live="statusFilter">
                    <option value="all">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}">{{ $status->label() }}</option>
                    @endforeach
                </x-filter-select>
            </x-slot:controls>
        </x-page-toolbar>

        <x-section-card title="Fleet registry" flush>
            <x-data-table>
                <x-table.head>
                    <tr>
                        <x-table.th>Vehicle</x-table.th>
                        <x-table.th>Type</x-table.th>
                        <x-table.th responsive="md">Site</x-table.th>
                        <x-table.th>Status</x-table.th>
                        <x-table.th responsive="lg">Odometer</x-table.th>
                        <x-table.th align="right">Actions</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse ($vehicles as $vehicle)
                        <tr class="table-row-hover" wire:key="fleet-{{ $vehicle->id }}">
                            <x-table.td>
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $vehicle->plate_number }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $vehicle->name ?: trim(($vehicle->make ?? '').' '.($vehicle->model ?? '')) ?: '—' }}</div>
                            </x-table.td>
                            <x-table.td>{{ $vehicle->type->label() }}</x-table.td>
                            <x-table.td responsive="md" muted>{{ $vehicle->site?->name ?? '—' }}</x-table.td>
                            <x-table.td><x-badge :status="$vehicle->status->value" /></x-table.td>
                            <x-table.td responsive="lg" muted class="tabular-nums">{{ $vehicle->current_odometer !== null ? number_format($vehicle->current_odometer) : '—' }}</x-table.td>
                            <x-table.td align="right">
                                <div class="table-inline-actions justify-end">
                                    <button type="button" class="table-action" wire:click="openEdit({{ $vehicle->id }})">Edit</button>
                                    @if ($vehicle->status->value !== 'available')
                                        <button type="button" class="table-action" wire:click="setStatus({{ $vehicle->id }}, 'available')">Available</button>
                                    @endif
                                    @if ($vehicle->status->value !== 'maintenance')
                                        <button type="button" class="table-action" wire:click="setStatus({{ $vehicle->id }}, 'maintenance')">Maintenance</button>
                                    @endif
                                    <button type="button" class="table-action-danger" wire:click="delete({{ $vehicle->id }})" wire:confirm="Delete this vehicle?">Delete</button>
                                </div>
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="6">
                            <x-empty-state title="No vehicles yet" description="Add cars and motors to the fleet, then assign them on Vehicle Patrols.">
                                <x-slot:actions>
                                    <x-button size="sm" wire:click="openCreate">Add vehicle</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>
        </x-section-card>

        <div class="mt-4">{{ $vehicles->links() }}</div>
    </x-page-shell>

    @if ($showForm)
        <x-drawer
            :title="$editingId ? 'Edit vehicle' : 'Add vehicle'"
            :description="$editingId ? 'Update plate, status, and home site.' : 'Register a car, motor, or van for patrol assign.'"
            width="lg"
            close-method="closeDrawer"
        >
            <x-drawer-form wire:submit.prevent="save" :submit-label="$editingId ? 'Update vehicle' : 'Save vehicle'" close-method="closeDrawer" target="save">
                <x-form-section title="Vehicle">
                    <x-select wire:model="form.type" label="Type *">
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="form.plate_number" label="Plate number *" placeholder="GR-1234-20" />
                    <x-input wire:model="form.name" label="Name / callsign" placeholder="Night runner" class="sm:col-span-2" />
                    <x-input wire:model="form.make" label="Make" placeholder="Toyota" />
                    <x-input wire:model="form.model" label="Model" placeholder="Hilux" />
                </x-form-section>
                <x-form-section title="Assignment">
                    <x-select wire:model="form.site_id" label="Home site">
                        <option value="">Unassigned</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model="form.status" label="Status *">
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="form.current_odometer" label="Odometer" type="number" min="0" />
                    <x-input wire:model="form.notes" label="Notes" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
