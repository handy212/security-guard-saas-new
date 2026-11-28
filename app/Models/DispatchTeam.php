<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DispatchTeam extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = ['tenant_id', 'name', 'standby_point_id'];

    public function standbyPoint(): BelongsTo
    {
        return $this->belongsTo(DispatchStandbyPoint::class, 'standby_point_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Guard::class);
    }

    public function outstandingDispatches(): HasMany
    {
        return $this->hasMany(DispatchEvent::class)->whereNull('returned_at');
    }

    public function activeDeployment(): HasOne
    {
        return $this->hasOne(ResponseDeployment::class)->whereNull('closed_at');
    }
}
