<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispatchTeam extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'standby_site_id'];

    public function standbySite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'standby_site_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(Guard::class);
    }

    public function outstandingDispatches(): HasMany
    {
        return $this->hasMany(DispatchEvent::class)->whereNull('returned_at');
    }
}
