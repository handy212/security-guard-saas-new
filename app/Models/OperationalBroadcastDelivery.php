<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OperationalBroadcastDelivery extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'connection', 'last_attempt_at', 'sent_at', 'failed_at'];

    protected function casts(): array
    {
        return [
            'last_attempt_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }
}
