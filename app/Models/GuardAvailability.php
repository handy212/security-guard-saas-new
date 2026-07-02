<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardAvailability extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'guard_id', 'weekday', 'starts_at', 'ends_at', 'is_available',
    ];

    protected function casts(): array
    {
        return ['is_available' => 'boolean'];
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function getDayOfWeekAttribute(): ?int
    {
        return is_numeric($this->weekday) ? (int) $this->weekday : null;
    }

    public function getStartTimeAttribute(): ?string
    {
        return $this->starts_at;
    }

    public function getEndTimeAttribute(): ?string
    {
        return $this->ends_at;
    }
}
