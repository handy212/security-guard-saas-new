<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class InvoiceItem extends Model { use BelongsToTenant; protected $fillable=['tenant_id','invoice_id','description','quantity','unit_price','line_total']; public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); } }
