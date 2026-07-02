<?php

namespace App\Livewire\Assets;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\EquipmentAsset;
use App\Services\AssetManagementService;
use App\Support\TenantContext;
use Livewire\Component;

class InventoryIndex extends Component
{
    use AuthorizesModuleAccess;

    public string $search = '';

    public function mount(): void
    {
        $this->authorizePolicy('viewAny', EquipmentAsset::class);
    }

    public function render(AssetManagementService $assets)
    {
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
