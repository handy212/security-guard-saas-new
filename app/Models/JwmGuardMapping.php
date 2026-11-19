<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JwmGuardMapping extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'jwm_device_id', 'external_id', 'external_id_hash', 'guard_id'];

    protected $hidden = ['external_id_hash'];

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }
}
