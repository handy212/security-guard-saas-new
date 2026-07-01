<div>
    @php
        $active = $vehiclePatrols->filter(fn ($p) => $p->end_odometer === null)->count();
        $completed = $vehiclePatrols->filter(fn ($p) => $p->end_odometer !== null)->count();
    @endphp

    <x-page-shell title="Vehicle Patrols" description="Mobile patrols with odometer readings.">
        <div class="grid grid-cols-4 gap-2">
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
            <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                <tr>
                    <th class="px-3 py-2">Vehicle</th>
                    <th class="px-3 py-2">Driver</th>
                    <th class="px-3 py-2">Odometer</th>
                    <th class="px-3 py-2">Logged</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vehiclePatrols as $patrol)
                    <tr class="table-row-hover">
                        <td class="px-3 py-2 font-medium">{{ $patrol->vehicle_number }}</td>
                        <td class="px-3 py-2 text-zinc-600">{{ $patrol->driver_name ?: '—' }}</td>
                        <td class="px-3 py-2 text-zinc-600">{{ $patrol->start_odometer ?? '—' }} → {{ $patrol->end_odometer ?? 'in progress' }}</td>
                        <td class="px-3 py-2 text-zinc-600">{{ $patrol->created_at?->format('M j, H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-8"><x-empty-state title="No vehicle patrols" /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>
    </x-page-shell>
</div>
