<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardIdleAlert extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'guard_id', 'last_location_at', 'idle_minutes', 'alerted_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'last_location_at' => 'datetime',
            'alerted_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }
}
