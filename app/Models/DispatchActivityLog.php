<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DispatchActivityLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'dispatch_event_id', 'user_id', 'action', 'message', 'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function dispatchEvent(): BelongsTo
    {
        return $this->belongsTo(DispatchEvent::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
