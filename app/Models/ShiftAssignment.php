<?php

namespace App\Models;

use App\Enums\ShiftAssignmentStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShiftAssignment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'shift_id', 'guard_id', 'status', 'assigned_at', 'confirmed_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'status' => ShiftAssignmentStatus::class,
        ];
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(ShiftConfirmation::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function swapRequests(): HasMany
    {
        return $this->hasMany(ShiftSwapRequest::class);
    }

    public function equipmentAssignments(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class);
    }
}
