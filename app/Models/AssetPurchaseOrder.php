<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetPurchaseOrder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'vendor_id', 'po_number', 'status', 'order_date', 'expected_date',
        'received_date', 'subtotal', 'tax_total', 'grand_total', 'notes', 'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'order_date' => 'date',
            'expected_date' => 'date',
            'received_date' => 'date',
            'subtotal' => 'float',
            'tax_total' => 'float',
            'grand_total' => 'float',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(AssetVendor::class, 'vendor_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AssetPurchaseOrderItem::class, 'purchase_order_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(EquipmentAsset::class, 'purchase_order_id');
    }
}
