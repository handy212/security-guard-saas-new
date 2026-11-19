<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JwmCheckpointMapping extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'jwm_device_id', 'external_id', 'external_id_hash', 'patrol_checkpoint_id'];

    protected $hidden = ['external_id_hash'];

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(PatrolCheckpoint::class, 'patrol_checkpoint_id');
    }
}
