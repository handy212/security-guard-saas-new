<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostOrder extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'site_id', 'site_post_id', 'title', 'instructions', 'version', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopePublishedForShift(Builder $query, Shift $shift): Builder
    {
        return $query->where('tenant_id', $shift->tenant_id)
            ->where('site_id', $shift->site_id)
            ->where('is_active', true)
            ->where(function (Builder $query) use ($shift): void {
                $query->whereNull('site_post_id');
                if ($shift->site_post_id !== null) {
                    $query->orWhere('site_post_id', $shift->site_post_id);
                }
            })
            ->orderByRaw('CASE WHEN site_post_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('title');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function sitePost(): BelongsTo
    {
        return $this->belongsTo(SitePost::class);
    }
}
