<div>
    <x-page-shell title="Vendors" description="Suppliers for uniforms, radios, vehicles, and equipment.">
        <x-slot:actions>
            <x-button wire:click="openCreate">Add vendor</x-button>
        </x-slot:actions>

        
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-assets-nav /></x-slot:sidebar>


        <x-flash-status />

        <x-page-toolbar search="search" searchPlaceholder="Search vendors…" />

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Vendor</x-table.th>
                    <x-table.th responsive="md">Contact</x-table.th>
                    <x-table.th>POs</x-table.th>
                    <x-table.th>Assets</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th align="right" class="w-12"></x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($vendors as $vendor)
                    <tr class="table-row-hover" wire:key="vendor-{{ $vendor->id }}">
                        <x-table.td>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $vendor->name }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $vendor->email }}</div>
                        </x-table.td>
                        <x-table.td responsive="md" muted>{{ $vendor->contact_name ?? '—' }} · {{ $vendor->phone ?? '—' }}</x-table.td>
                        <x-table.td class="tabular-nums">{{ $vendor->purchase_orders_count }}</x-table.td>
                        <x-table.td class="tabular-nums">{{ $vendor->assets_count }}</x-table.td>
                        <x-table.td><x-badge :status="$vendor->status" /></x-table.td>
                        <x-table.td align="right">
                            <x-row-menu>
                                <x-row-menu-item wire:click="edit({{ $vendor->id }})">Edit</x-row-menu-item>
                                <x-row-menu-item wire:click="delete({{ $vendor->id }})" wire:confirm="Delete vendor?" danger>Delete</x-row-menu-item>
                            </x-row-menu>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="6">
                        <x-empty-state compact title="No vendors" />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$vendors" />
            </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($showForm)
        <x-drawer :title="$editingId ? 'Edit vendor' : 'Add vendor'" width="md">
            <x-drawer-form wire:submit.prevent="save" :submit-label="$editingId ? 'Update' : 'Create'">
                <x-input wire:model="form.name" label="Company name *" class="sm:col-span-2" />
                <x-input wire:model="form.contact_name" label="Contact name" />
                <x-input wire:model="form.phone" label="Phone" />
                <x-input wire:model="form.email" type="email" label="Email" class="sm:col-span-2" />
                <x-textarea wire:model="form.address" label="Address" rows="2" class="sm:col-span-2" />
                <x-select wire:model="form.status" label="Status" class="sm:col-span-2">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </x-select>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
