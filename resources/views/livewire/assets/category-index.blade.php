<div>
    <x-page-shell title="Asset categories" description="Organize assets by type and set stock thresholds.">
        <x-slot:actions>
            <x-button wire:click="openCreate">Add category</x-button>
        </x-slot:actions>

        
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-assets-nav /></x-slot:sidebar>


        <x-flash-status />

        <x-page-toolbar search="search" searchPlaceholder="Search categories…" />

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Category</x-table.th>
                    <x-table.th>Type</x-table.th>
                    <x-table.th>Min stock</x-table.th>
                    <x-table.th>Assets</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th align="right" class="w-12"></x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($categories as $category)
                    <tr class="table-row-hover" wire:key="cat-{{ $category->id }}">
                        <x-table.td>
                            <div class="font-medium">{{ $category->name }}</div>
                            <div class="text-xs text-zinc-500">{{ Str::limit($category->description, 60) }}</div>
                        </x-table.td>
                        <x-table.td muted>{{ $types[$category->type] ?? $category->type }}</x-table.td>
                        <x-table.td>{{ $category->type === 'consumable' ? $category->min_stock_level : '—' }}</x-table.td>
                        <x-table.td>{{ $category->assets_count }}</x-table.td>
                        <x-table.td><x-badge :status="$category->is_active ? 'active' : 'inactive'" /></x-table.td>
                        <x-table.td align="right">
                            <x-row-menu>
                                <x-row-menu-item wire:click="edit({{ $category->id }})">Edit</x-row-menu-item>
                                <x-row-menu-item wire:click="delete({{ $category->id }})" wire:confirm="Delete category?" danger>Delete</x-row-menu-item>
                            </x-row-menu>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="6">
                        <x-empty-state compact title="No categories" />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$categories" />
            </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($showForm)
        <x-drawer :title="$editingId ? 'Edit category' : 'Add category'" width="md">
            <x-drawer-form wire:submit.prevent="save" :submit-label="$editingId ? 'Update' : 'Create'">
                <x-input wire:model="form.name" label="Name *" class="sm:col-span-2" />
                <x-select wire:model="form.type" label="Type *" class="sm:col-span-2">
                    @foreach($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.min_stock_level" type="number" min="0" label="Min stock level" class="sm:col-span-2" />
                <x-textarea wire:model="form.description" label="Description" rows="3" class="sm:col-span-2" />
                <label class="flex items-center gap-2 text-sm sm:col-span-2">
                    <input type="checkbox" wire:model="form.is_active" class="rounded border-zinc-300">
                    Active
                </label>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
