<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class JwmDeviceEvent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'jwm_device_id', 'event_id', 'payload_hash', 'type', 'occurred_at',
        'guard_id', 'guard_location_id', 'checkpoint_scan_id', 'guard_external_id', 'checkpoint_external_id',
    ];

    protected $hidden = ['payload_hash'];

    protected function casts(): array
    {
        return ['occurred_at' => 'immutable_datetime'];
    }
}
