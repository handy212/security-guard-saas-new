<?php

namespace App\Livewire\Assets;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\AssetPurchaseOrder;
use App\Models\EquipmentAsset;
use App\Models\EquipmentAssignment;
use App\Services\AssetManagementService;
use App\Support\TenantContext;
use Livewire\Component;

class Overview extends Component
{
    use AuthorizesModuleAccess;

    public function mount(): void
    {
        $this->authorizePolicy('viewAny', EquipmentAsset::class);
    }

    public function render(AssetManagementService $assets)
    {
        $tenantId = TenantContext::id();

        return view('livewire.assets.overview', [
            'stats' => $assets->overviewStats($tenantId),
            'recentAssignments' => EquipmentAssignment::with(['asset', 'assignedGuard', 'site'])
                ->where('tenant_id', $tenantId)
                ->latest()
                ->limit(8)
                ->get(),
            'openOrders' => AssetPurchaseOrder::with('vendor')
                ->where('tenant_id', $tenantId)
                ->whereNotIn('status', ['received', 'cancelled'])
                ->latest()
                ->limit(6)
                ->get(),
            'warrantyAlerts' => EquipmentAsset::with('assetCategory')
                ->where('tenant_id', $tenantId)
                ->whereNotNull('warranty_expires_at')
                ->where('warranty_expires_at', '<=', now()->addDays(60))
                ->orderBy('warranty_expires_at')
                ->limit(6)
                ->get(),
            'lowStock' => $assets->inventoryByCategory($tenantId)->where('is_low_stock', true),
        ])->layout('layouts.app');
    }
}
