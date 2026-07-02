<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PassdownLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'site_id', 'site_post_id', 'guard_id', 'shift_assignment_id', 'content',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function sitePost(): BelongsTo
    {
        return $this->belongsTo(SitePost::class);
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }
}
