<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetVendor extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'contact_name', 'email', 'phone', 'address', 'status',
    ];

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(AssetPurchaseOrder::class, 'vendor_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(EquipmentAsset::class, 'vendor_id');
    }
}
