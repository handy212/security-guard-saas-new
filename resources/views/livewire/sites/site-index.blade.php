<div>
    <x-page-header title="Sites & Geofences" description="Client locations with GPS coordinates and geofence radius for attendance validation." />

    <div class="space-y-5 p-6">
        <x-form-card title="Add or edit site" collapsible>
            <form wire:submit="save" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <x-select wire:model="form.client_account_id" label="Client">
                    <option value="">Select client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.name" label="Site name" />
                <x-input wire:model="form.address" label="Address" class="md:col-span-2" />
                <x-input wire:model="form.latitude" label="Latitude" type="number" step="any" />
                <x-input wire:model="form.longitude" label="Longitude" type="number" step="any" />
                <x-input wire:model="form.geofence_radius_meters" label="Geofence radius (m)" type="number" hint="Guards must be within this radius to clock in." />
                <div class="md:col-span-2 xl:col-span-3">
                    <x-button type="submit">Save site</x-button>
                </div>
            </form>
        </x-form-card>

        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search sites…" />

        <x-data-table title="All sites">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Site</th>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Address</th>
                    <th class="px-4 py-3">Geofence</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sites as $site)
                    <tr class="table-row-hover">
                        <td class="px-4 py-3 font-medium">{{ $site->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $site->clientAccount?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-slate-600">{{ $site->address ?: '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">{{ $site->latitude }}, {{ $site->longitude }} · {{ $site->geofence_radius_meters }}m</td>
                        <td class="px-4 py-3"><x-badge :status="$site->status" /></td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="edit({{ $site->id }})" class="btn-link">Edit</button>
                            <button wire:click="delete({{ $site->id }})" wire:confirm="Delete this site?" class="ml-3 text-sm font-medium text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10"><x-empty-state title="No sites configured" description="Sites are required for shifts, patrols, and geofenced attendance." action="/sites" actionLabel="Add site" /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>

        {{ $sites->links('components.pagination') }}
    </div>
</div>
