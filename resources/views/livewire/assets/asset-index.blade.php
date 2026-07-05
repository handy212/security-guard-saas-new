<div>
    <x-page-shell title="Assets" description="Manage radios, uniforms, vehicles, and issued gear.">
        <x-slot:actions>
            <x-button wire:click="openCreate">Add asset</x-button>
        </x-slot:actions>

        <x-assets-nav />
        <x-flash-status />

        <x-page-toolbar search="search" searchPlaceholder="Search assets…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="collect($statuses)->prepend('All', 'all')->all()" />
            </x-slot:tabs>
            <x-slot:controls>
                <x-filter-select wire:model.live="categoryFilter">
                    <option value="">All categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </x-filter-select>
            </x-slot:controls>
        </x-page-toolbar>

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Asset</x-table.th>
                    <x-table.th responsive="md">Category</x-table.th>
                    <x-table.th>Tag / Serial</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th responsive="lg">Assigned</x-table.th>
                    <x-table.th align="right" class="w-12"></x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($items as $item)
                    <tr class="table-row-hover" wire:key="asset-{{ $item->id }}">
                        <x-table.td>
                            <div class="font-medium">{{ $item->name }}</div>
                            <div class="text-xs text-zinc-500">{{ $item->vendor?->name ?? '—' }}</div>
                        </x-table.td>
                        <x-table.td responsive="md" muted>{{ $item->assetCategory?->name ?? $item->category ?? '—' }}</x-table.td>
                        <x-table.td muted>
                            <div>{{ $item->asset_tag ?: '—' }}</div>
                            <div class="text-xs">{{ $item->serial_number }}</div>
                        </x-table.td>
                        <x-table.td><x-badge :status="$item->status->value" /></x-table.td>
                        <x-table.td responsive="lg" muted>{{ $item->assignments->first()?->assignedGuard?->full_name ?? '—' }}</x-table.td>
                        <x-table.td align="right">
                            <x-row-menu>
                                @if($item->status->value === 'available')
                                    <x-row-menu-item wire:click="openIssue({{ $item->id }})">Issue</x-row-menu-item>
                                @endif
                                @if($item->assignments->first())
                                    <x-row-menu-item wire:click="returnAssignment({{ $item->assignments->first()->id }})">Return</x-row-menu-item>
                                @endif
                                <x-row-menu-item wire:click="edit({{ $item->id }})">Edit</x-row-menu-item>
                                <x-row-menu-item wire:click="delete({{ $item->id }})" wire:confirm="Delete this asset?" danger>Delete</x-row-menu-item>
                            </x-row-menu>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="6">
                        <x-empty-state compact title="No assets" description="Add assets or receive a purchase order.">
                            <x-slot:actions><x-button size="sm" wire:click="openCreate">Add asset</x-button></x-slot:actions>
                        </x-empty-state>
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$items" />
    </x-page-shell>

    @if ($showForm)
        <x-drawer :title="$editingId ? 'Edit asset' : 'Add asset'" width="lg">
            <x-drawer-form wire:submit.prevent="save" :submit-label="$editingId ? 'Update asset' : 'Create asset'">
                <x-select wire:model="form.asset_category_id" label="Category" class="sm:col-span-2">
                    <option value="">Select category</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </x-select>
                <x-select wire:model="form.vendor_id" label="Vendor">
                    <option value="">None</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                    @endforeach
                </x-select>
                <x-select wire:model="form.site_id" label="Site">
                    <option value="">None</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.name" label="Name *" class="sm:col-span-2" />
                <x-input wire:model="form.asset_tag" label="Asset tag" />
                <x-input wire:model="form.serial_number" label="Serial number" />
                <x-input wire:model="form.model" label="Model" />
                <x-input wire:model="form.manufacturer" label="Manufacturer" />
                <x-input wire:model="form.purchase_cost" type="number" step="0.01" label="Purchase cost" />
                <x-input wire:model="form.purchase_date" type="date" label="Purchase date" />
                <x-input wire:model="form.warranty_expires_at" type="date" label="Warranty expires" />
                <x-input wire:model="form.location" label="Storage location" class="sm:col-span-2" />
                <x-input wire:model="form.quantity_on_hand" type="number" min="1" label="Qty on hand" />
                <x-select wire:model="form.condition" label="Condition">
                    <option value="good">Good</option>
                    <option value="fair">Fair</option>
                    <option value="poor">Poor</option>
                </x-select>
                <x-select wire:model="form.status" label="Status">
                    @foreach($statuses as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-select>
                <x-textarea wire:model="form.description" label="Description" rows="2" class="sm:col-span-2" />
                <x-textarea wire:model="form.notes" label="Notes" rows="2" class="sm:col-span-2" />
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($showIssueForm)
        <x-drawer title="Issue asset" width="md" close-method="closeIssueForm">
            <x-drawer-form wire:submit.prevent="issue" submit-label="Issue asset" close-method="closeIssueForm" target="issue">
                <x-select wire:model="issueForm.guard_id" label="Guard" class="sm:col-span-2">
                    <option value="">Select guard</option>
                    @foreach($guards as $guard)
                        <option value="{{ $guard->id }}">{{ $guard->full_name }}</option>
                    @endforeach
                </x-select>
                <x-select wire:model="issueForm.site_id" label="Site" class="sm:col-span-2">
                    <option value="">Select site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-select>
                <x-textarea wire:model="issueForm.issue_notes" label="Issue notes" rows="3" class="sm:col-span-2" />
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
