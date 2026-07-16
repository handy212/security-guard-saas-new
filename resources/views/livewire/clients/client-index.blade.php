<div>
    <x-page-shell
        title="Clients"
        description="Manage client accounts, billing rates, and contacts."
        :breadcrumbs="[['label' => 'Clients']]"
    >
        <x-slot:actions>
            <x-button wire:click="openCreate">Add client</x-button>
        </x-slot:actions>

        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$clientStats['total']" icon="users" wire:click="applyStatFilter('total')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'all' && $search === ''" />
            <x-stat-card compact label="Active" :value="$clientStats['active']" icon="check" tone="success" wire:click="applyStatFilter('active')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'active'" />
            <x-stat-card compact label="With email" :value="$clientStats['with_email']" icon="billing" tone="info" class="text-left" />
            <x-stat-card compact label="Inactive" :value="$clientStats['inactive']" icon="pause" wire:click="applyStatFilter('inactive')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'inactive'" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search clients…">
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
                    <x-table.th>Name</x-table.th>
                    <x-table.th responsive="md">Email</x-table.th>
                    <x-table.th responsive="lg">Phone</x-table.th>
                    <x-table.th>Rate</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th align="right" class="w-12"></x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($clients as $client)
                    <tr class="table-row-hover" wire:key="client-{{ $client->id }}">
                        <x-table.td class="font-medium">
                            <a href="{{ route('clients.show', $client) }}" class="font-medium text-accent-700 hover:underline">{{ $client->name }}</a>
                        </x-table.td>
                        <x-table.td responsive="md" muted>{{ $client->email ?: '—' }}</x-table.td>
                        <x-table.td responsive="lg" muted>{{ $client->phone ?: '—' }}</x-table.td>
                        <x-table.td muted>{{ $client->default_monthly_rate ? number_format($client->default_monthly_rate, 2) : '—' }}</x-table.td>
                        <x-table.td><x-badge :status="$client->status" /></x-table.td>
                        <x-table.td align="right">
                            <x-row-menu>
                                <x-row-menu-item :href="route('clients.show', $client)">Open profile</x-row-menu-item>
                                <x-row-menu-item wire:click="edit({{ $client->id }})">Quick edit</x-row-menu-item>
                                <x-row-menu-item wire:click="delete({{ $client->id }})" wire:confirm="Delete this client?" danger>Delete</x-row-menu-item>
                            </x-row-menu>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="6">
                        <x-empty-state
                            compact
                            :title="$hasActiveFilters ? 'No matching clients' : 'No clients yet'"
                            :description="$hasActiveFilters ? 'Try adjusting your filters.' : 'Start with a client, then add sites and assign guards.'"
                        >
                            <x-slot:actions>
                                @if (! $hasActiveFilters)
                                    <x-button size="sm" wire:click="openCreate">Add client</x-button>
                                @else
                                    <button type="button" wire:click="clearFilters" class="table-action">Clear filters</button>
                                @endif
                            </x-slot:actions>
                        </x-empty-state>
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$clients" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer
            :title="$editingId ? 'Edit client' : 'Add client'"
            :description="$editingId ? 'Update account details.' : 'Start here, then add sites and assign guards.'"
            width="lg"
        >
            <x-drawer-form wire:submit="save" :submit-label="$editingId ? 'Update client' : 'Create client'">
                <x-form-section title="Company">
                    <x-input wire:model="form.name" label="Client name" class="sm:col-span-2" />
                    <x-input wire:model="form.industry" label="Industry" />
                    <x-input wire:model="form.default_monthly_rate" label="Default monthly rate" type="number" step="0.01" />
                </x-form-section>

                <x-form-section title="Contact">
                    <x-input wire:model="form.email" label="Email" type="email" />
                    <x-input wire:model="form.phone" label="Phone" />
                    <x-input wire:model="form.address" label="Address" class="sm:col-span-2" />
                </x-form-section>

                <x-form-section title="HQ coordinates" description="Optional — used on the client map overview.">
                    <x-input wire:model="form.latitude" label="Latitude" type="number" step="any" />
                    <x-input wire:model="form.longitude" label="Longitude" type="number" step="any" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
