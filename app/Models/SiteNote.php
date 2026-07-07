<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteNote extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'site_id', 'user_id', 'body', 'is_internal',
    ];

    protected function casts(): array
    {
        return ['is_internal' => 'boolean'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
