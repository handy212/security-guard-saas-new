<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchStandbyPoint extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'region_id', 'name', 'address', 'latitude', 'longitude', 'radius_meters'];

    protected function casts(): array
    {
        return ['latitude' => 'float', 'longitude' => 'float'];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'region_id');
    }
}
