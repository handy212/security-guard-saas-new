<div>
    <x-page-shell
        title="Sites & Geofences"
        description="Client locations with GPS and geofence radius."
        :breadcrumbs="[['label' => 'Sites']]"
    >
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
                        <x-table.td>
                            <div class="min-w-0">
                                <a href="{{ route('sites.show', $site) }}" class="font-medium text-zinc-900 transition hover:text-accent-700 dark:text-zinc-100 dark:hover:text-accent-300">{{ $site->name }}</a>
                                <div class="truncate text-xs text-zinc-500 md:hidden dark:text-zinc-400">{{ $site->clientAccount?->name ?? 'No client' }}</div>
                            </div>
                        </x-table.td>
                        <x-table.td responsive="md" muted>{{ $site->clientAccount?->name ?? '—' }}</x-table.td>
                        <x-table.td responsive="lg" muted>{{ $site->address ?: '—' }}</x-table.td>
                        <x-table.td><span class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $site->geofence_radius_meters }}m</span></x-table.td>
                        <x-table.td><x-badge :status="$site->status" /></x-table.td>
                        <x-table.td align="right">
                            <x-row-menu>
                                <x-row-menu-item :href="route('sites.show', $site)">Open profile</x-row-menu-item>
                                <x-row-menu-item wire:click="edit({{ $site->id }})">Quick edit</x-row-menu-item>
                                <x-row-menu-item wire:click="delete({{ $site->id }})" wire:confirm="Delete this site?" danger>Delete</x-row-menu-item>
                            </x-row-menu>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="6">
                        <x-empty-state
                            compact
                            :title="$hasActiveFilters ? 'No matching sites' : 'No sites yet'"
                            :description="$hasActiveFilters ? 'Try adjusting your filters.' : 'Add a site after creating a client to schedule and track coverage.'"
                        >
                            <x-slot:actions>
                                @if (! $hasActiveFilters)
                                    <x-button size="sm" wire:click="openCreate">Add site</x-button>
                                    <x-button size="sm" variant="secondary" :href="route('clients.index')">View clients</x-button>
                                @else
                                    <button type="button" wire:click="clearFilters" class="table-action">Clear filters</button>
                                @endif
                            </x-slot:actions>
                        </x-empty-state>
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$sites" per-page="perPage" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer
            :title="$editingId ? 'Edit site' : 'Add site'"
            :description="$editingId ? 'Update location and geofence basics.' : 'Link a client location. Assign posts and patrols from the site profile.'"
            width="lg"
        >
            <x-drawer-form wire:submit="save" :submit-label="$editingId ? 'Update site' : 'Create site'">
                <x-form-section title="Site">
                    <x-select wire:model="form.client_account_id" label="Client" class="sm:col-span-2" hint="Create the client first if they are not listed.">
                        <option value="">Select client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="form.name" label="Site name" placeholder="Main Gate" />
                    <x-select wire:model="form.status" label="Status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </x-select>
                    <x-input wire:model="form.address" label="Address" class="sm:col-span-2" />
                </x-form-section>

                <x-form-section title="Geofence" description="Used for clock-in validation and live map.">
                    <x-input wire:model="form.latitude" label="Latitude" type="number" step="any" />
                    <x-input wire:model="form.longitude" label="Longitude" type="number" step="any" />
                    <x-input wire:model="form.geofence_radius_meters" label="Radius (meters)" type="number" class="sm:col-span-2" hint="Typical coverage is 100–300m." />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
