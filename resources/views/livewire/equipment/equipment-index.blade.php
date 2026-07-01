<div>
    <x-page-shell title="Equipment" description="Track radios, uniforms, vehicles, and issued gear.">
        <x-slot:actions>
            <x-button wire:click="openCreate">Add asset</x-button>
        </x-slot:actions>

        <x-stat-grid>
            <x-stat-card compact label="Total assets" :value="$stats['total']" icon="billing" />
            <x-stat-card compact label="Available" :value="$stats['available']" icon="check" tone="success" />
            <x-stat-card compact label="Issued" :value="$stats['issued']" icon="guards" tone="info" />
            <x-stat-card compact label="Retired" :value="$stats['retired']" icon="pause" />
        </x-stat-grid>

        <x-page-toolbar search="search" searchPlaceholder="Search equipment…">
            <x-slot:tabs>
                <x-segment-control model="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'available' => 'Available', 'issued' => 'Issued', 'retired' => 'Retired']" />
            </x-slot:tabs>
        </x-page-toolbar>

        <x-data-table>
            <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                <tr>
                    <th class="px-3 py-2">Asset</th>
                    <th class="px-3 py-2">Tag</th>
                    <th class="px-3 py-2">Condition</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr class="table-row-hover" wire:key="equipment-{{ $item->id }}">
                        <td class="px-3 py-2">
                            <div class="font-medium">{{ $item->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $item->category ?: '—' }}</div>
                        </td>
                        <td class="px-3 py-2 text-zinc-600">{{ $item->asset_tag ?: '—' }}</td>
                        <td class="px-3 py-2"><x-badge :status="$item->condition" /></td>
                        <td class="px-3 py-2"><x-badge :status="$item->status" /></td>
                        <td class="px-3 py-2 text-right">
                            <button type="button" wire:click="edit({{ $item->id }})" class="btn-link">Edit</button>
                            <button type="button" wire:click="delete({{ $item->id }})" wire:confirm="Delete?" class="ml-2 text-xs text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-3 py-8"><x-empty-state title="No equipment" /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$items" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer :title="$editingId ? 'Edit asset' : 'Add asset'" width="lg">
            <form wire:submit="save" class="grid gap-3 sm:grid-cols-2">
                <x-input wire:model="form.name" label="Name" class="sm:col-span-2" />
                <x-input wire:model="form.asset_tag" label="Asset tag" />
                <x-input wire:model="form.category" label="Category" />
                <x-input wire:model="form.serial_number" label="Serial number" class="sm:col-span-2" />
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
                <div class="flex gap-2 sm:col-span-2">
                    <x-button type="submit">{{ $editingId ? 'Update' : 'Create' }}</x-button>
                    <x-button type="button" variant="secondary" wire:click="closeDrawer">Cancel</x-button>
                </div>
            </form>
        </x-drawer>
    @endif
</div>
