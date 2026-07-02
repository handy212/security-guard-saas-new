<?php

namespace App\Models;

use App\Enums\AssetStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EquipmentAsset extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'asset_category_id', 'vendor_id', 'purchase_order_id', 'site_id',
        'asset_tag', 'name', 'category', 'description', 'serial_number', 'model', 'manufacturer',
        'purchase_cost', 'purchase_date', 'warranty_expires_at', 'location', 'quantity_on_hand',
        'condition', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => AssetStatus::class,
            'purchase_cost' => 'float',
            'purchase_date' => 'date',
            'warranty_expires_at' => 'date',
        ];
    }

    public function assetCategory(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(AssetVendor::class, 'vendor_id');
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(AssetPurchaseOrder::class, 'purchase_order_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class, 'equipment_asset_id');
    }
}
