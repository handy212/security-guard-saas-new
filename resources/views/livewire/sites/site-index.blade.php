<div>
    <x-page-shell title="Sites & Geofences" description="Client locations with GPS and geofence radius.">
        <x-slot:actions>
            <x-button wire:click="openCreate">Add site</x-button>
        </x-slot:actions>

        <x-stat-grid>
            <x-stat-card compact label="Total" :value="$siteStats['total']" icon="plan" wire:click="applyStatFilter('total')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'all' && $search === ''" />
            <x-stat-card compact label="Active" :value="$siteStats['active']" icon="check" tone="success" wire:click="applyStatFilter('active')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'active'" />
            <x-stat-card compact label="Geofenced" :value="$siteStats['geofenced']" icon="guards" tone="info" class="text-left" />
            <x-stat-card compact label="Inactive" :value="$siteStats['inactive']" icon="pause" wire:click="applyStatFilter('inactive')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'inactive'" />
        </x-stat-grid>

        <x-page-toolbar search="search" searchPlaceholder="Search sites…">
            <x-slot:tabs>
                <x-segment-control model="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'active' => 'Active', 'inactive' => 'Inactive']" />
            </x-slot:tabs>
            <x-slot:controls>
                @if ($hasActiveFilters)
                    <button type="button" wire:click="clearFilters" class="text-xs font-medium text-zinc-500 hover:text-zinc-800">Clear filters</button>
                @endif
            </x-slot:controls>
        </x-page-toolbar>

        <x-data-table>
            <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                <tr>
                    <th class="px-3 py-2">Site</th>
                    <th class="hidden px-3 py-2 md:table-cell">Client</th>
                    <th class="hidden px-3 py-2 lg:table-cell">Address</th>
                    <th class="px-3 py-2">Geofence</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right w-12"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($sites as $site)
                    <tr class="table-row-hover" wire:key="site-{{ $site->id }}">
                        <td class="px-3 py-2 font-medium">{{ $site->name }}</td>
                        <td class="hidden px-3 py-2 text-zinc-600 md:table-cell">{{ $site->clientAccount?->name ?? '—' }}</td>
                        <td class="hidden px-3 py-2 text-sm text-zinc-600 lg:table-cell">{{ $site->address ?: '—' }}</td>
                        <td class="px-3 py-2 text-xs text-zinc-500">{{ $site->geofence_radius_meters }}m</td>
                        <td class="px-3 py-2"><x-badge :status="$site->status" /></td>
                        <td class="px-3 py-2 text-right">
                            <div x-data="{ open: false }" class="relative inline-block text-left">
                                <button type="button" @click="open = !open" class="rounded-md p-1.5 text-zinc-500 hover:bg-zinc-100" aria-label="Actions">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 4a2 2 0 110-4 2 2 0 010 4zm0 4a2 2 0 110-4 2 2 0 010 4z"/></svg>
                                </button>
                                <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-10 mt-1 w-32 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg">
                                    <button type="button" wire:click="edit({{ $site->id }})" @click="open = false" class="block w-full px-3 py-1.5 text-left text-sm text-zinc-700 hover:bg-zinc-50">Edit</button>
                                    <button type="button" wire:click="delete({{ $site->id }})" wire:confirm="Delete this site?" @click="open = false" class="block w-full px-3 py-1.5 text-left text-sm text-red-600 hover:bg-red-50">Delete</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-8"><x-empty-state :title="$hasActiveFilters ? 'No matching sites' : 'No sites'" /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$sites" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer :title="$editingId ? 'Edit site' : 'Add site'" width="lg">
            <form wire:submit="save" class="grid gap-3 sm:grid-cols-2">
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
                <div class="flex gap-2 sm:col-span-2">
                    <x-button type="submit" loading-text="Saving…">Save</x-button>
                    <x-button type="button" variant="secondary" wire:click="closeDrawer">Cancel</x-button>
                </div>
            </form>
        </x-drawer>
    @endif
</div>
