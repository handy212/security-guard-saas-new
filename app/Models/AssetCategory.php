<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssetCategory extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'description', 'type', 'min_stock_level', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function assets(): HasMany
    {
        return $this->hasMany(EquipmentAsset::class, 'asset_category_id');
    }

    public function isConsumable(): bool
    {
        return $this->type === 'consumable';
    }
}
