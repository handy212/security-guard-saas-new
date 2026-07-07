<div>
    <x-page-shell title="Purchase orders" description="Procure assets and receive into inventory.">
        <x-slot:actions>
            <x-button wire:click="$set('showForm', true)">New purchase order</x-button>
        </x-slot:actions>

        
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-assets-nav /></x-slot:sidebar>


        <x-flash-status />

        <div class="grid gap-4 lg:grid-cols-5">
            <div class="lg:col-span-2">
                <x-data-table>
                    <x-table.head>
                        <tr>
                            <x-table.th>PO #</x-table.th>
                            <x-table.th>Vendor</x-table.th>
                            <x-table.th>Status</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse($orders as $po)
                            <tr
                                class="cursor-pointer table-row-hover {{ $selectedId === $po->id ? 'bg-sky-50' : '' }}"
                                wire:click="selectPo({{ $po->id }})"
                                wire:key="po-row-{{ $po->id }}"
                            >
                                <x-table.td>
                                    <div class="font-medium">{{ $po->po_number }}</div>
                                    <div class="text-xs text-zinc-500">{{ $po->order_date?->format('M j, Y') }}</div>
                                </x-table.td>
                                <x-table.td muted>{{ $po->vendor?->name }}</x-table.td>
                                <x-table.td><x-badge :status="$po->status->value" /></x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="3">
                                <x-empty-state compact title="No purchase orders" />
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
                <x-pagination :paginator="$orders" />
            </div>

            <div class="lg:col-span-3">
                @if($selected)
                    <x-section-card :title="$selected->po_number">
                        <div class="mb-4 grid gap-2 text-sm sm:grid-cols-2">
                            <div><span class="text-zinc-500">Vendor</span><div class="font-medium">{{ $selected->vendor?->name }}</div></div>
                            <div><span class="text-zinc-500">Status</span><div><x-badge :status="$selected->status->value" /></div></div>
                            <div><span class="text-zinc-500">Expected</span><div>{{ $selected->expected_date?->format('M j, Y') ?? '—' }}</div></div>
                            <div><span class="text-zinc-500">Total</span><div class="font-medium">${{ number_format($selected->grand_total, 2) }}</div></div>
                        </div>

                        <x-data-table>
                            <x-table.head>
                                <tr>
                                    <x-table.th>Item</x-table.th>
                                    <x-table.th>Qty</x-table.th>
                                    <x-table.th>Received</x-table.th>
                                    <x-table.th align="right">Action</x-table.th>
                                </tr>
                            </x-table.head>
                            <tbody>
                                @foreach($selected->items as $item)
                                    <tr wire:key="po-item-{{ $item->id }}">
                                        <x-table.td>
                                            <div class="font-medium">{{ $item->description }}</div>
                                            <div class="text-xs text-zinc-500">{{ $item->category?->name }}</div>
                                        </x-table.td>
                                        <x-table.td>{{ $item->quantity }}</x-table.td>
                                        <x-table.td>{{ $item->quantity_received }}</x-table.td>
                                        <x-table.td align="right">
                                            @if($item->remainingQuantity() > 0)
                                                <x-button size="sm" wire:click="receiveLine({{ $selected->id }}, {{ $item->id }})">Receive</x-button>
                                            @else
                                                <span class="text-xs text-emerald-600">Complete</span>
                                            @endif
                                        </x-table.td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </x-data-table>

                        <div class="mt-4 flex flex-wrap gap-2">
                            @if($selected->status->value === 'draft')
                                <x-button size="sm" wire:click="submitPo({{ $selected->id }})">Submit</x-button>
                            @endif
                            @if(in_array($selected->status->value, ['submitted', 'draft']))
                                <x-button size="sm" variant="secondary" wire:click="markOrdered({{ $selected->id }})">Mark ordered</x-button>
                            @endif
                        </div>
                    </x-section-card>
                @else
                    <x-section-card title="PO detail">
                        <x-empty-state compact title="Select a purchase order" />
                    </x-section-card>
                @endif
            </div>
        </div>
            </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($showForm)
        <x-drawer title="New purchase order" width="lg">
            <x-drawer-form wire:submit.prevent="save" submit-label="Create PO">
                <x-select wire:model="form.vendor_id" label="Vendor *" class="sm:col-span-2">
                    <option value="">Select vendor</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.expected_date" type="date" label="Expected delivery" />
                <x-input wire:model="form.tax_total" type="number" step="0.01" label="Tax" />
                <x-textarea wire:model="form.notes" label="Notes" rows="2" class="sm:col-span-2" />

                <div class="sm:col-span-2 rounded-lg border border-zinc-200 p-3">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-sm font-semibold">Line items</h3>
                        <button type="button" wire:click="addLine" class="text-xs font-medium text-sky-600">+ Add line</button>
                    </div>
                    @foreach($items as $index => $item)
                        <div class="mb-3 grid gap-2 border-t border-zinc-100 pt-3 sm:grid-cols-6" wire:key="line-{{ $index }}">
                            <x-select wire:model="items.{{ $index }}.asset_category_id" label="Category" class="sm:col-span-2">
                                <option value="">Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </x-select>
                            <x-input wire:model="items.{{ $index }}.description" label="Description *" class="sm:col-span-2" />
                            <x-input wire:model="items.{{ $index }}.quantity" type="number" min="1" label="Qty" />
                            <x-input wire:model="items.{{ $index }}.unit_cost" type="number" step="0.01" label="Unit cost" />
                        </div>
                    @endforeach
                </div>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
