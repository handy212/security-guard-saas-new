<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'client_account_id',
        'billing_period',
        'invoice_number',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'tax_total',
        'grand_total',
        'amount_paid',
        'sent_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'billing_period' => 'date',
            'sent_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['sent', 'partial', 'overdue']);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('status', 'overdue')
                ->orWhere(function (Builder $query) {
                    $query->whereIn('status', ['sent', 'partial'])
                        ->whereDate('due_date', '<', today());
                });
        });
    }
}
