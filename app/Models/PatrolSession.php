<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PatrolSession extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'patrol_route_id', 'guard_id', 'shift_assignment_id',
        'status', 'started_at', 'completed_at', 'notes', 'completion_percent',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function route(): BelongsTo
    {
        return $this->belongsTo(PatrolRoute::class, 'patrol_route_id');
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function shiftAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(CheckpointScan::class);
    }

    public function vehiclePatrol(): HasOne
    {
        return $this->hasOne(VehiclePatrol::class, 'patrol_session_id')->latestOfMany();
    }

    public function vehiclePatrols(): HasMany
    {
        return $this->hasMany(VehiclePatrol::class, 'patrol_session_id');
    }
}
