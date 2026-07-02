<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetPurchaseOrderItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'purchase_order_id', 'asset_category_id', 'description',
        'quantity', 'quantity_received', 'unit_cost', 'line_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_cost' => 'float',
            'line_total' => 'float',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(AssetPurchaseOrder::class, 'purchase_order_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function remainingQuantity(): int
    {
        return max(0, $this->quantity - $this->quantity_received);
    }
}
