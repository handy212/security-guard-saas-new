<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardDocument extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'guard_id', 'type', 'file_path', 'expires_at', 'status',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'date'];
    }

    public function guard(): BelongsTo
    {
        return $this->belongsTo(Guard::class);
    }
}
