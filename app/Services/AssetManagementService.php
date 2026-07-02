<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\AssetCategory;
use App\Models\AssetPurchaseOrder;
use App\Models\AssetPurchaseOrderItem;
use App\Models\AssetVendor;
use App\Models\EquipmentAsset;
use App\Models\EquipmentAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssetManagementService
{
    public function issue(EquipmentAsset $asset, array $data): EquipmentAssignment
    {
        $asset->update(['status' => AssetStatus::ISSUED]);

        return EquipmentAssignment::create([
            'tenant_id' => $asset->tenant_id,
            'equipment_asset_id' => $asset->id,
            'guard_id' => $data['guard_id'] ?? null,
            'site_id' => $data['site_id'] ?? null,
            'issue_notes' => $data['issue_notes'] ?? null,
            'issued_at' => now(),
            'status' => 'issued',
        ]);
    }

    public function returnAsset(EquipmentAssignment $assignment, ?string $notes = null): EquipmentAssignment
    {
        $assignment->update([
            'returned_at' => now(),
            'return_notes' => $notes,
            'status' => 'returned',
        ]);

        $assignment->asset?->update(['status' => AssetStatus::AVAILABLE]);

        return $assignment;
    }

    public function createPurchaseOrder(array $header, array $items, int $userId): AssetPurchaseOrder
    {
        return DB::transaction(function () use ($header, $items, $userId) {
            $subtotal = collect($items)->sum(fn ($item) => (float) $item['quantity'] * (float) $item['unit_cost']);

            $po = AssetPurchaseOrder::create([
                'tenant_id' => $header['tenant_id'],
                'vendor_id' => $header['vendor_id'],
                'po_number' => $this->nextPoNumber($header['tenant_id']),
                'status' => PurchaseOrderStatus::DRAFT,
                'order_date' => $header['order_date'] ?? now()->toDateString(),
                'expected_date' => $header['expected_date'] ?? null,
                'subtotal' => $subtotal,
                'tax_total' => $header['tax_total'] ?? 0,
                'grand_total' => $subtotal + ($header['tax_total'] ?? 0),
                'notes' => $header['notes'] ?? null,
                'created_by_user_id' => $userId,
            ]);

            foreach ($items as $item) {
                $lineTotal = (float) $item['quantity'] * (float) $item['unit_cost'];
                AssetPurchaseOrderItem::create([
                    'tenant_id' => $header['tenant_id'],
                    'purchase_order_id' => $po->id,
                    'asset_category_id' => $item['asset_category_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => (int) $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'line_total' => $lineTotal,
                ]);
            }

            return $po->fresh(['vendor', 'items.category']);
        });
    }

    public function submitPurchaseOrder(AssetPurchaseOrder $po): AssetPurchaseOrder
    {
        $po->update(['status' => PurchaseOrderStatus::SUBMITTED]);

        return $po;
    }

    public function markOrdered(AssetPurchaseOrder $po): AssetPurchaseOrder
    {
        $po->update([
            'status' => PurchaseOrderStatus::ORDERED,
            'order_date' => $po->order_date ?? now()->toDateString(),
        ]);

        return $po;
    }

    public function receiveItems(AssetPurchaseOrder $po, int $itemId, int $quantity): AssetPurchaseOrder
    {
        return DB::transaction(function () use ($po, $itemId, $quantity) {
            $item = AssetPurchaseOrderItem::where('purchase_order_id', $po->id)->findOrFail($itemId);
            $receiveQty = min($quantity, $item->remainingQuantity());
            abort_unless($receiveQty > 0, 422, 'Nothing left to receive for this line.');

            $item->increment('quantity_received', $receiveQty);

            $category = $item->asset_category_id
                ? AssetCategory::find($item->asset_category_id)
                : null;

            if ($category?->isConsumable()) {
                $existing = EquipmentAsset::query()
                    ->where('tenant_id', $po->tenant_id)
                    ->where('asset_category_id', $category->id)
                    ->where('status', AssetStatus::AVAILABLE)
                    ->first();

                if ($existing) {
                    $existing->increment('quantity_on_hand', $receiveQty);
                } else {
                    EquipmentAsset::create([
                        'tenant_id' => $po->tenant_id,
                        'asset_category_id' => $category->id,
                        'vendor_id' => $po->vendor_id,
                        'purchase_order_id' => $po->id,
                        'name' => $item->description,
                        'category' => $category->name,
                        'purchase_cost' => $item->unit_cost,
                        'purchase_date' => now()->toDateString(),
                        'quantity_on_hand' => $receiveQty,
                        'status' => AssetStatus::AVAILABLE,
                        'condition' => 'good',
                    ]);
                }
            } else {
                for ($i = 0; $i < $receiveQty; $i++) {
                    EquipmentAsset::create([
                        'tenant_id' => $po->tenant_id,
                        'asset_category_id' => $item->asset_category_id,
                        'vendor_id' => $po->vendor_id,
                        'purchase_order_id' => $po->id,
                        'name' => $item->description,
                        'category' => $category?->name,
                        'asset_tag' => $this->nextAssetTag($po->tenant_id),
                        'purchase_cost' => $item->unit_cost,
                        'purchase_date' => now()->toDateString(),
                        'quantity_on_hand' => 1,
                        'status' => AssetStatus::AVAILABLE,
                        'condition' => 'good',
                    ]);
                }
            }

            $this->syncPurchaseOrderStatus($po->fresh(['items']));

            return $po->fresh(['vendor', 'items.category']);
        });
    }

    public function overviewStats(int $tenantId): array
    {
        $assets = EquipmentAsset::where('tenant_id', $tenantId);
        $categories = AssetCategory::where('tenant_id', $tenantId)->where('is_active', true);

        return [
            'total_assets' => (clone $assets)->count(),
            'available' => (clone $assets)->where('status', AssetStatus::AVAILABLE)->count(),
            'issued' => (clone $assets)->where('status', AssetStatus::ISSUED)->count(),
            'maintenance' => (clone $assets)->where('status', AssetStatus::MAINTENANCE)->count(),
            'categories' => (clone $categories)->count(),
            'vendors' => AssetVendor::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'open_pos' => AssetPurchaseOrder::where('tenant_id', $tenantId)
                ->whereNotIn('status', [PurchaseOrderStatus::RECEIVED, PurchaseOrderStatus::CANCELLED])
                ->count(),
            'asset_value' => (clone $assets)->sum('purchase_cost'),
            'warranty_expiring' => (clone $assets)
                ->whereNotNull('warranty_expires_at')
                ->whereBetween('warranty_expires_at', [now(), now()->addDays(60)])
                ->count(),
        ];
    }

    public function inventoryByCategory(int $tenantId): Collection
    {
        return AssetCategory::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->withCount([
                'assets',
                'assets as available_count' => fn ($q) => $q->where('status', AssetStatus::AVAILABLE),
                'assets as issued_count' => fn ($q) => $q->where('status', AssetStatus::ISSUED),
            ])
            ->withSum('assets as stock_quantity', 'quantity_on_hand')
            ->orderBy('name')
            ->get()
            ->map(function (AssetCategory $category) {
                $onHand = (int) ($category->stock_quantity ?? 0);
                $category->on_hand = $onHand;
                $category->is_low_stock = $category->isConsumable()
                    && $category->min_stock_level > 0
                    && $onHand < $category->min_stock_level;

                return $category;
            });
    }

    private function syncPurchaseOrderStatus(AssetPurchaseOrder $po): void
    {
        $items = $po->items;
        $allReceived = $items->every(fn ($item) => $item->quantity_received >= $item->quantity);
        $anyReceived = $items->contains(fn ($item) => $item->quantity_received > 0);

        $status = match (true) {
            $allReceived => PurchaseOrderStatus::RECEIVED,
            $anyReceived => PurchaseOrderStatus::PARTIAL,
            default => $po->status,
        };

        $po->update([
            'status' => $status,
            'received_date' => $allReceived ? now()->toDateString() : $po->received_date,
        ]);
    }

    private function nextPoNumber(int $tenantId): string
    {
        $year = now()->year;
        $count = AssetPurchaseOrder::where('tenant_id', $tenantId)->whereYear('created_at', $year)->count() + 1;

        return sprintf('PO-%d-%04d', $year, $count);
    }

    private function nextAssetTag(int $tenantId): string
    {
        $count = EquipmentAsset::where('tenant_id', $tenantId)->count() + 1;

        return sprintf('AST-%05d', $count);
    }
}
