<div class="directory-page clients-page">
    @if ($showComplaints)
        <livewire:clients.complaint-board />
    @else
    <x-page-shell
        title="Clients"
        description="Manage client accounts, billing rates, and contacts."
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('clients.index', ['tab' => 'complaints'])">Client complaints</x-button>
            <x-button wire:click="openCreate">Add client</x-button>
        </x-slot:actions>

        <section class="directory-panel" aria-label="Client directory">
        <x-page-toolbar search="search" searchPlaceholder="Search clients…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All ('.$clientStats['total'].')', 'active' => 'Active ('.$clientStats['active'].')', 'inactive' => 'Inactive ('.$clientStats['inactive'].')']" />
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
                    <x-table.th>Client</x-table.th>
                    <x-table.th responsive="md">Contact</x-table.th>
                    <x-table.th responsive="lg">Monthly rate</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th align="right" class="w-12"><span class="sr-only">Actions</span></x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($clients as $client)
                    <tr class="table-row-hover" wire:key="client-{{ $client->id }}">
                        <x-table.td>
                            <div class="flex items-center gap-2.5">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-[10px] font-semibold text-zinc-600 ring-1 ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700">
                                    {{ strtoupper(substr($client->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('clients.show', $client) }}" class="font-medium text-zinc-900 transition hover:text-accent-700 dark:text-zinc-100 dark:hover:text-accent-300">{{ $client->name }}</a>
                                    @if ($client->industry)
                                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $client->industry }}</div>
                                    @endif
                                    @if ($client->email)
                                        <div class="truncate text-xs text-zinc-500 md:hidden dark:text-zinc-400">{{ $client->email }}</div>
                                    @endif
                                </div>
                            </div>
                        </x-table.td>
                        <x-table.td responsive="md">
                            <div class="text-sm text-zinc-700 dark:text-zinc-300">{{ $client->email ?: 'No email added' }}</div>
                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $client->phone ?: 'No phone added' }}</div>
                        </x-table.td>
                        <x-table.td responsive="lg" muted>{{ $client->default_monthly_rate ? money($client->default_monthly_rate) : '—' }}</x-table.td>
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
                    <x-table.empty colspan="5">
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

        <x-pagination :paginator="$clients" per-page="perPage" />
        </section>
    </x-page-shell>

    @if ($showForm)
        <x-drawer
            :title="$editingId ? 'Edit client' : 'Add client'"
            :description="$editingId ? 'Update account details.' : 'Start here, then add sites and assign guards.'"
            width="lg"
        >
            <x-drawer-form wire:submit="save" :submit-label="$editingId ? 'Update client' : 'Create client'">
                <x-form-section title="Account details" description="Add the company and its default billing details.">
                    <x-input wire:model.live="form.name" label="Client name" required autocomplete="organization" placeholder="Company or organization name" class="sm:col-span-2" />
                    <x-input wire:model.live="form.industry" label="Industry" />
                    <x-input wire:model.live="form.default_monthly_rate" label="Default monthly rate" type="number" step="0.01" />
                    <x-select wire:model.live="form.status" label="Status" class="sm:col-span-2">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </x-select>
                </x-form-section>

                <x-form-section title="Contact">
                    <x-input wire:model.live="form.email" label="Email" type="email" />
                    <x-input wire:model.live="form.phone" label="Phone" />
                    <x-input wire:model.live="form.address" label="Address" class="sm:col-span-2" />
                </x-form-section>

                <details class="directory-disclosure" @if ($errors->hasAny(['form.latitude', 'form.longitude'])) open @endif>
                    <summary>Map location <span>Optional</span></summary>
                    <div class="mt-5">                <x-form-section title="HQ coordinates" description="Optional — used on the client map overview.">
                    <x-input wire:model.live="form.latitude" label="Latitude" type="number" step="any" />
                    <x-input wire:model.live="form.longitude" label="Longitude" type="number" step="any" />
                </x-form-section></div>
                </details>
            </x-drawer-form>
        </x-drawer>
    @endif
    @endif
</div>
