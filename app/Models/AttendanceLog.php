<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'shift_assignment_id', 'guard_id', 'site_id', 'type', 'recorded_at',
        'latitude', 'longitude', 'is_geofence_valid', 'clock_in_at', 'clock_out_at',
        'clock_in_latitude', 'clock_in_longitude', 'clock_out_latitude', 'clock_out_longitude',
        'geofence_validated', 'worked_minutes', 'status',
        'reconciled_at', 'reconciled_by_user_id', 'reconciliation_notes', 'original_status',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
            'reconciled_at' => 'datetime',
            'geofence_validated' => 'boolean',
            'is_geofence_valid' => 'boolean',
            'status' => AttendanceStatus::class,
        ];
    }

    public function shiftAssignment(): BelongsTo
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by_user_id');
    }

    public function breakLogs(): HasMany
    {
        return $this->hasMany(BreakLog::class);
    }
}
