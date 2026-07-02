<div>
    @php
        $active = $vehiclePatrols->filter(fn ($p) => $p->end_odometer === null)->count();
        $completed = $vehiclePatrols->filter(fn ($p) => $p->end_odometer !== null)->count();
    @endphp

    <x-page-shell title="Vehicle Patrols" description="Mobile patrols with odometer readings.">
        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$vehiclePatrols->count()" icon="patrols" />
            <x-stat-card compact label="Active" :value="$active" icon="gps" tone="info" />
            <x-stat-card compact label="Completed" :value="$completed" icon="check" tone="success" />
            <x-stat-card compact label="In progress" :value="$active" icon="schedules" :tone="$active ? 'warning' : 'default'" />
        </div>

        <x-form-card title="Start vehicle patrol" description="Log vehicle number, driver, and odometer readings." collapsible open>
            <form wire:submit="save" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <x-input wire:model="form.vehicle_number" label="Vehicle number" placeholder="VAN-01" required />
                <x-input wire:model="form.driver_name" label="Driver" placeholder="John Smith" />
                <x-input wire:model="form.start_odometer" label="Start odometer" type="number" placeholder="12000" />
                <x-input wire:model="form.end_odometer" label="End odometer" type="number" placeholder="12045" />
                <div class="md:col-span-2 xl:col-span-4">
                    <x-button type="submit">Save patrol</x-button>
                </div>
            </form>
        </x-form-card>

        <x-data-table title="Recent vehicle patrols">
            <x-table.head>
                <tr>
                    <x-table.th>Vehicle</x-table.th>
                    <x-table.th>Driver</x-table.th>
                    <x-table.th>Odometer</x-table.th>
                    <x-table.th>Logged</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($vehiclePatrols as $patrol)
                    <tr class="table-row-hover" wire:key="vpatrol-{{ $patrol->id }}">
                        <x-table.td><span class="font-medium text-zinc-900">{{ $patrol->vehicle_number }}</span></x-table.td>
                        <x-table.td muted>{{ $patrol->driver_name ?: '—' }}</x-table.td>
                        <x-table.td muted>{{ $patrol->start_odometer ?? '—' }} → {{ $patrol->end_odometer ?? 'in progress' }}</x-table.td>
                        <x-table.td muted>{{ $patrol->created_at?->format('M j, H:i') }}</x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="4"><x-empty-state title="No vehicle patrols" /></x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>
    </x-page-shell>
</div>
