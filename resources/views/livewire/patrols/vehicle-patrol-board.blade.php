<div>
    <x-page-header title="Vehicle Patrols" description="Mobile patrols with odometer readings and fuel logs." />

    @php
        $active = $vehiclePatrols->filter(fn ($p) => $p->end_odometer === null)->count();
        $completed = $vehiclePatrols->filter(fn ($p) => $p->end_odometer !== null)->count();
    @endphp

    <div class="grid gap-4 px-6 pb-4 md:grid-cols-3">
        <x-stat-card label="Total patrols" :value="$vehiclePatrols->count()" />
        <x-stat-card label="Active" :value="$active" tone="info" />
        <x-stat-card label="Completed" :value="$completed" tone="success" />
    </div>

    <div class="space-y-5 p-6 pt-0">
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
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Vehicle</th>
                    <th class="px-4 py-3">Driver</th>
                    <th class="px-4 py-3">Odometer</th>
                    <th class="px-4 py-3">Logged</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vehiclePatrols as $patrol)
                    <tr class="table-row-hover">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $patrol->vehicle_number }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $patrol->driver_name ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $patrol->start_odometer ?? '—' }} → {{ $patrol->end_odometer ?? 'in progress' }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $patrol->created_at?->format('M j, H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10"><x-empty-state title="No vehicle patrols" description="Log your first vehicle patrol above." /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>
    </div>
</div>
