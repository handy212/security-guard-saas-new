<div>
    <x-page-header title="Equipment Assets" description="Track radios, uniforms, vehicles, and other issued gear." />

    <div class="space-y-5 p-6">
        <x-form-card :title="$editingId ? 'Edit asset' : 'Add asset'" description="Register equipment and track condition and status." collapsible :open="!$editingId">
            <form wire:submit="save" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <x-input wire:model="form.name" label="Name" placeholder="Motorola radio" required />
                <x-input wire:model="form.asset_tag" label="Asset tag" placeholder="EQ-001" />
                <x-input wire:model="form.category" label="Category" placeholder="Radio, uniform…" />
                <x-input wire:model="form.serial_number" label="Serial number" placeholder="SN12345" />
                <x-select wire:model="form.condition" label="Condition">
                    <option value="good">Good</option>
                    <option value="fair">Fair</option>
                    <option value="poor">Poor</option>
                </x-select>
                <x-select wire:model="form.status" label="Status">
                    <option value="available">Available</option>
                    <option value="issued">Issued</option>
                    <option value="retired">Retired</option>
                </x-select>
                <div class="flex items-end gap-2 md:col-span-2 xl:col-span-3">
                    <x-button type="submit">{{ $editingId ? 'Update asset' : 'Create asset' }}</x-button>
                    @if($editingId)
                        <x-button type="button" variant="secondary" wire:click="$set('editingId', null)">Cancel</x-button>
                    @endif
                </div>
            </form>
        </x-form-card>

        <x-search-input wire:model.live.debounce.300ms="search" placeholder="Search by name, tag, or category…" />

        <x-data-table title="All equipment">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Asset</th>
                    <th class="px-4 py-3">Tag</th>
                    <th class="px-4 py-3">Condition</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="table-row-hover">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ $item->name }}</div>
                            <div class="text-xs text-slate-500">{{ $item->category ?: '—' }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $item->asset_tag ?: '—' }}</td>
                        <td class="px-4 py-3"><x-badge :status="$item->condition" /></td>
                        <td class="px-4 py-3"><x-badge :status="$item->status" /></td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="edit({{ $item->id }})" class="btn-link">Edit</button>
                            <button wire:click="delete({{ $item->id }})" wire:confirm="Delete this asset?" class="ml-3 text-sm font-medium text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10"><x-empty-state title="No equipment yet" description="Add your first asset to start tracking issued gear." /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>
    </div>
</div>
