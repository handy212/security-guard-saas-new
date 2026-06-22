<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class Invoice extends Model { use BelongsToTenant; protected $fillable=['tenant_id','client_account_id','invoice_number','invoice_date','due_date','status','subtotal','tax_total','grand_total','sent_at','paid_at']; protected function casts(): array { return ['invoice_date'=>'date','due_date'=>'date','sent_at'=>'datetime','paid_at'=>'datetime']; } public function clientAccount(): BelongsTo { return $this->belongsTo(ClientAccount::class); } public function items(): HasMany { return $this->hasMany(InvoiceItem::class); } }
