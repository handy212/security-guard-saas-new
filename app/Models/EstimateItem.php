<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EstimateItem extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'estimate_id', 'description', 'quantity', 'unit_price', 'line_total', 'is_taxable',
    ];

    protected function casts(): array
    {
        return ['is_taxable' => 'boolean'];
    }

    public function estimate(): BelongsTo
    {
        return $this->belongsTo(Estimate::class);
    }
}
