<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WebhookSubscription extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'event', 'target_url', 'secret', 'is_active', 'last_delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_delivered_at' => 'datetime',
        ];
    }
}
