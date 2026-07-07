<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardNote extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'guard_id', 'user_id', 'body', 'is_internal',
    ];

    protected function casts(): array
    {
        return ['is_internal' => 'boolean'];
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
