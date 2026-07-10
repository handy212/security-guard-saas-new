<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehiclePatrol extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'vehicle_id', 'guard_id', 'patrol_session_id', 'vehicle_number', 'driver_name',
        'start_odometer', 'end_odometer', 'status', 'started_at', 'ended_at', 'fuel_log',
    ];

    protected function casts(): array
    {
        return [
            'fuel_log' => 'array',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(FleetVehicle::class, 'vehicle_id');
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function patrolSession(): BelongsTo
    {
        return $this->belongsTo(PatrolSession::class, 'patrol_session_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active' || ($this->end_odometer === null && $this->ended_at === null);
    }
}
