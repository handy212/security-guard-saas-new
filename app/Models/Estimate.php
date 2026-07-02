<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estimate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'client_account_id', 'estimate_number', 'estimate_date', 'valid_until',
        'status', 'subtotal', 'tax_total', 'grand_total', 'sent_at', 'accepted_at', 'converted_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'estimate_date' => 'date',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(EstimateItem::class);
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }
}
