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

        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-[12rem] flex-1">
                <x-input wire:model.live.debounce.300ms="search" label="Search" placeholder="Plate, name, make…" />
            </div>
            <x-select wire:model.live="typeFilter" label="Type" class="w-36">
                <option value="all">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </x-select>
            <x-select wire:model.live="statusFilter" label="Status" class="w-40">
                <option value="all">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </x-select>
        </div>

        @if ($showForm)
            <x-form-card :title="$editingId ? 'Edit vehicle' : 'Add vehicle'" collapsible open>
                <form wire:submit="save" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <x-select wire:model="form.type" label="Type" required>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="form.plate_number" label="Plate number" required placeholder="GR-1234-20" />
                    <x-input wire:model="form.name" label="Name / callsign" placeholder="Night runner" />
                    <x-input wire:model="form.make" label="Make" placeholder="Toyota" />
                    <x-input wire:model="form.model" label="Model" placeholder="Hilux" />
                    <x-select wire:model="form.site_id" label="Home site">
                        <option value="">Unassigned</option>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                    <x-select wire:model="form.status" label="Status" required>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}">{{ $status->label() }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="form.current_odometer" label="Odometer" type="number" min="0" />
                    <div class="md:col-span-2 xl:col-span-3">
                        <x-input wire:model="form.notes" label="Notes" />
                    </div>
                    <div class="flex gap-2 md:col-span-2 xl:col-span-3">
                        <x-button type="submit">{{ $editingId ? 'Update' : 'Save' }}</x-button>
                        <x-button type="button" variant="secondary" wire:click="$set('showForm', false)">Cancel</x-button>
                    </div>
                </form>
            </x-form-card>
        @endif

        <x-data-table title="Fleet registry">
            <x-table.head>
                <tr>
                    <x-table.th>Vehicle</x-table.th>
                    <x-table.th>Type</x-table.th>
                    <x-table.th responsive="md">Site</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th responsive="lg">Odometer</x-table.th>
                    <x-table.th>Actions</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse ($vehicles as $vehicle)
                    <tr class="table-row-hover" wire:key="fleet-{{ $vehicle->id }}">
                        <x-table.td>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $vehicle->plate_number }}</div>
                            <div class="text-xs text-zinc-500">{{ $vehicle->name ?: trim(($vehicle->make ?? '').' '.($vehicle->model ?? '')) ?: '—' }}</div>
                        </x-table.td>
                        <x-table.td>{{ $vehicle->type->label() }}</x-table.td>
                        <x-table.td responsive="md" muted>{{ $vehicle->site?->name ?? '—' }}</x-table.td>
                        <x-table.td><x-badge :status="$vehicle->status->value" /></x-table.td>
                        <x-table.td responsive="lg" muted>{{ $vehicle->current_odometer !== null ? number_format($vehicle->current_odometer) : '—' }}</x-table.td>
                        <x-table.td>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="text-xs font-medium text-accent-600 hover:underline" wire:click="openEdit({{ $vehicle->id }})">Edit</button>
                                <button type="button" class="text-xs font-medium text-red-600 hover:underline" wire:click="delete({{ $vehicle->id }})" wire:confirm="Delete this vehicle?">Delete</button>
                                    @if ($vehicle->status->value !== 'available')
                                    <button type="button" class="text-xs font-medium text-zinc-600 hover:underline" wire:click="setStatus({{ $vehicle->id }}, 'available')">Mark available</button>
                                    @endif
                                    @if ($vehicle->status->value !== 'maintenance')
                                    <button type="button" class="text-xs font-medium text-amber-700 hover:underline" wire:click="setStatus({{ $vehicle->id }}, 'maintenance')">Maintenance</button>
                                    @endif
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

        <div class="mt-4">{{ $vehicles->links() }}</div>
    </x-page-shell>
</div>
