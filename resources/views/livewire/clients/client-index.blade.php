<div>
    <x-page-header title="Clients" description="Manage client accounts, billing rates, and contacts." />

    <div class="space-y-5 p-6">
        <x-form-card :title="$editingId ? 'Edit client' : 'Add client'" description="Create or update a client account." collapsible :open="!$editingId">
            <form wire:submit="save" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <x-input wire:model="form.name" label="Client name" placeholder="Acme Security Client" />
                <x-input wire:model="form.industry" label="Industry" placeholder="Mining, retail…" />
                <x-input wire:model="form.email" label="Email" type="email" placeholder="contact@client.com" />
                <x-input wire:model="form.phone" label="Phone" placeholder="+234…" />
                <x-input wire:model="form.default_hourly_rate" label="Default hourly rate" type="number" step="0.01" placeholder="25.00" />
                <div class="flex items-end gap-2 md:col-span-2 xl:col-span-3">
                    <x-button type="submit" wire:submit="save">{{ $editingId ? 'Update client' : 'Create client' }}</x-button>
                    @if($editingId)
                        <x-button type="button" variant="secondary" wire:click="$set('editingId', null)">Cancel</x-button>
                    @endif
                </div>
            </form>
        </x-form-card>

        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search clients by name or email…" />

        <x-data-table title="All clients">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Rate</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    <tr class="table-row-hover">
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $client->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $client->email ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $client->phone ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $client->default_hourly_rate ? number_format($client->default_hourly_rate, 2) : '—' }}</td>
                        <td class="px-4 py-3"><x-badge :status="$client->status" /></td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="edit({{ $client->id }})" class="btn-link">Edit</button>
                            <button wire:click="delete({{ $client->id }})" wire:confirm="Delete this client?" class="ml-3 text-sm font-medium text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10"><x-empty-state title="No clients yet" description="Add your first client to start managing sites and contracts." action="/clients" actionLabel="Add client" /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>

        {{ $clients->links('components.pagination') }}
    </div>
</div>
