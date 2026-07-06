<?php

namespace App\Models;

use App\Enums\ConfirmationStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftConfirmation extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'shift_assignment_id', 'guard_id', 'status', 'confirmed_at',
    ];

    protected function casts(): array
    {
        return ['confirmed_at' => 'datetime', 'status' => ConfirmationStatus::class];
    }

    public function shiftAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }
}
