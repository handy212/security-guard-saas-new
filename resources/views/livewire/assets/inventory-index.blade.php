<div>
    <x-page-shell title="Asset inventory" description="Stock levels and availability by category.">
        
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-assets-nav /></x-slot:sidebar>


        <x-flash-status />

        <div class="stat-grid">
            <x-stat-card compact label="Categories" :value="$inventory->count()" icon="equipment" />
            <x-stat-card compact label="Total on hand" :value="$totalOnHand" icon="check" tone="success" />
            <x-stat-card compact label="Low stock" :value="$lowStockCount" icon="incidents" :tone="$lowStockCount ? 'danger' : 'success'" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Filter categories…" />

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Category</x-table.th>
                    <x-table.th>Type</x-table.th>
                    <x-table.th>On hand</x-table.th>
                    <x-table.th>Available</x-table.th>
                    <x-table.th>Issued</x-table.th>
                    <x-table.th>Min level</x-table.th>
                    <x-table.th>Status</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($inventory as $row)
                    <tr class="table-row-hover {{ $row->is_low_stock ? 'bg-red-50/40' : '' }}" wire:key="inv-{{ $row->id }}">
                        <x-table.td>
                            <a href="{{ route('assets.index', ['category' => $row->id]) }}" class="font-medium hover:underline">{{ $row->name }}</a>
                        </x-table.td>
                        <x-table.td muted>{{ $row->type === 'consumable' ? 'Consumable' : 'Serialized' }}</x-table.td>
                        <x-table.td class="font-semibold">{{ $row->on_hand }}</x-table.td>
                        <x-table.td>{{ $row->available_count }}</x-table.td>
                        <x-table.td>{{ $row->issued_count }}</x-table.td>
                        <x-table.td>{{ $row->type === 'consumable' ? $row->min_stock_level : '—' }}</x-table.td>
                        <x-table.td>
                            @if($row->is_low_stock)
                                <x-badge status="critical" />
                            @else
                                <x-badge status="active" />
                            @endif
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="7">
                        <x-empty-state compact title="No inventory data" description="Create categories and assets to populate inventory." />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>
            </x-sub-sidebar-layout>
    </x-page-shell>
</div>
