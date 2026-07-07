<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardSiteAssignment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'guard_id', 'site_id', 'is_primary', 'notes',
    ];

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
