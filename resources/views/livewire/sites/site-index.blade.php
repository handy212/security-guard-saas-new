<div>
    <x-page-shell title="Sites & Geofences" description="Client locations with GPS and geofence radius.">
        <x-slot:actions>
            <x-button wire:click="openCreate">Add site</x-button>
        </x-slot:actions>

        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$siteStats['total']" icon="plan" wire:click="applyStatFilter('total')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'all' && $search === ''" />
            <x-stat-card compact label="Active" :value="$siteStats['active']" icon="check" tone="success" wire:click="applyStatFilter('active')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'active'" />
            <x-stat-card compact label="Geofenced" :value="$siteStats['geofenced']" icon="guards" tone="info" class="text-left" />
            <x-stat-card compact label="Inactive" :value="$siteStats['inactive']" icon="pause" wire:click="applyStatFilter('inactive')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'inactive'" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search sites…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive']" />
            </x-slot:tabs>
            <x-slot:controls>
                @if ($hasActiveFilters)
                    <button type="button" wire:click="clearFilters" class="table-action">Clear filters</button>
                @endif
            </x-slot:controls>
        </x-page-toolbar>

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Site</x-table.th>
                    <x-table.th responsive="md">Client</x-table.th>
                    <x-table.th responsive="lg">Address</x-table.th>
                    <x-table.th>Geofence</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th align="right" class="w-12"></x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($sites as $site)
                    <tr class="table-row-hover" wire:key="site-{{ $site->id }}">
                        <x-table.td class="font-medium">{{ $site->name }}</x-table.td>
                        <x-table.td responsive="md" muted>{{ $site->clientAccount?->name ?? '—' }}</x-table.td>
                        <x-table.td responsive="lg" muted>{{ $site->address ?: '—' }}</x-table.td>
                        <x-table.td><span class="text-xs text-zinc-500">{{ $site->geofence_radius_meters }}m</span></x-table.td>
                        <x-table.td><x-badge :status="$site->status" /></x-table.td>
                        <x-table.td align="right">
                            <x-row-menu>
                                <x-row-menu-item wire:click="edit({{ $site->id }})">Edit</x-row-menu-item>
                                <x-row-menu-item wire:click="delete({{ $site->id }})" wire:confirm="Delete this site?" danger>Delete</x-row-menu-item>
                            </x-row-menu>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="6">
                        <x-empty-state compact :title="$hasActiveFilters ? 'No matching sites' : 'No sites'" />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$sites" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer :title="$editingId ? 'Edit site' : 'Add site'" width="lg">
            <x-drawer-form wire:submit="save" :submit-label="$editingId ? 'Update site' : 'Create site'">
                <x-select wire:model="form.client_account_id" label="Client" class="sm:col-span-2">
                    <option value="">Select client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.name" label="Site name" class="sm:col-span-2" />
                <x-input wire:model="form.address" label="Address" class="sm:col-span-2" />
                <x-input wire:model="form.latitude" label="Latitude" type="number" step="any" />
                <x-input wire:model="form.longitude" label="Longitude" type="number" step="any" />
                <x-input wire:model="form.geofence_radius_meters" label="Geofence radius (m)" type="number" class="sm:col-span-2" />
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
