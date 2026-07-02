<?php

namespace App\Livewire\Assets;

use App\Services\AssetManagementService;
use App\Support\TenantContext;
use Livewire\Component;

class InventoryIndex extends Component
{
    public string $search = '';

    public function render(AssetManagementService $assets)
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);

        $inventory = $assets->inventoryByCategory(TenantContext::id())
            ->when($this->search !== '', fn ($rows) => $rows->filter(
                fn ($row) => str_contains(strtolower($row->name), strtolower($this->search))
            ));

        return view('livewire.assets.inventory-index', [
            'inventory' => $inventory,
            'lowStockCount' => $inventory->where('is_low_stock', true)->count(),
            'totalOnHand' => $inventory->sum('on_hand'),
        ])->layout('layouts.app');
    }
}
