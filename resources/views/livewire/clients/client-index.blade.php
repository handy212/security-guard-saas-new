<div>
    <x-page-shell title="Clients" description="Manage client accounts, billing rates, and contacts.">
        <x-slot:actions>
            <x-button wire:click="openCreate">Add client</x-button>
        </x-slot:actions>

        <x-stat-grid>
            <x-stat-card compact label="Total" :value="$clientStats['total']" icon="users" wire:click="applyStatFilter('total')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'all' && $search === ''" />
            <x-stat-card compact label="Active" :value="$clientStats['active']" icon="check" tone="success" wire:click="applyStatFilter('active')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'active'" />
            <x-stat-card compact label="With email" :value="$clientStats['with_email']" icon="billing" tone="info" class="text-left" />
            <x-stat-card compact label="Inactive" :value="$clientStats['inactive']" icon="pause" wire:click="applyStatFilter('inactive')" class="cursor-pointer text-left transition hover:border-zinc-300" :active="$statusFilter === 'inactive'" />
        </x-stat-grid>

        <x-page-toolbar search="search" searchPlaceholder="Search clients…">
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
                    <th class="px-3 py-2">Name</th>
                    <th class="hidden px-3 py-2 md:table-cell">Email</th>
                    <th class="hidden px-3 py-2 lg:table-cell">Phone</th>
                    <th class="px-3 py-2">Rate</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right w-12"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    <tr class="table-row-hover" wire:key="client-{{ $client->id }}">
                        <td class="px-3 py-2 font-medium">{{ $client->name }}</td>
                        <td class="hidden px-3 py-2 text-zinc-600 md:table-cell">{{ $client->email ?: '—' }}</td>
                        <td class="hidden px-3 py-2 text-zinc-600 lg:table-cell">{{ $client->phone ?: '—' }}</td>
                        <td class="px-3 py-2 text-zinc-600">{{ $client->default_hourly_rate ? number_format($client->default_hourly_rate, 2) : '—' }}</td>
                        <td class="px-3 py-2"><x-badge :status="$client->status" /></td>
                        <td class="px-3 py-2 text-right">
                            <div x-data="{ open: false }" class="relative inline-block text-left">
                                <button type="button" @click="open = !open" class="rounded-md p-1.5 text-zinc-500 hover:bg-zinc-100" aria-label="Actions">
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 4a2 2 0 110-4 2 2 0 010 4zm0 4a2 2 0 110-4 2 2 0 010 4z"/></svg>
                                </button>
                                <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-10 mt-1 w-32 rounded-lg border border-zinc-200 bg-white py-1 shadow-lg">
                                    <button type="button" wire:click="edit({{ $client->id }})" @click="open = false" class="block w-full px-3 py-1.5 text-left text-sm text-zinc-700 hover:bg-zinc-50">Edit</button>
                                    <button type="button" wire:click="delete({{ $client->id }})" wire:confirm="Delete this client?" @click="open = false" class="block w-full px-3 py-1.5 text-left text-sm text-red-600 hover:bg-red-50">Delete</button>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-8"><x-empty-state :title="$hasActiveFilters ? 'No matching clients' : 'No clients yet'" /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$clients" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer :title="$editingId ? 'Edit client' : 'Add client'" width="lg">
            <form wire:submit="save" class="grid gap-3 sm:grid-cols-2">
                <x-input wire:model="form.name" label="Client name" class="sm:col-span-2" />
                <x-input wire:model="form.industry" label="Industry" />
                <x-input wire:model="form.email" label="Email" type="email" />
                <x-input wire:model="form.phone" label="Phone" />
                <x-input wire:model="form.default_hourly_rate" label="Default hourly rate" type="number" step="0.01" />
                <div class="flex gap-2 sm:col-span-2">
                    <x-button type="submit" loading-text="{{ $editingId ? 'Updating…' : 'Creating…' }}">{{ $editingId ? 'Update' : 'Create' }}</x-button>
                    <x-button type="button" variant="secondary" wire:click="closeDrawer">Cancel</x-button>
                </div>
            </form>
        </x-drawer>
    @endif
</div>
