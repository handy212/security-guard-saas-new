<?php

namespace App\Models;

use App\Enums\DispatchPriority;
use App\Enums\DispatchStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DispatchEvent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'dispatch_number', 'client_account_id', 'site_id', 'guard_id', 'created_by_user_id',
        'event_type', 'priority', 'caller_type', 'caller_name', 'incident_location',
        'incident_date', 'incident_time', 'status', 'description', 'action_taken', 'internal_notes',
        'attachment_path', 'latitude', 'longitude', 'sos_alert_id', 'incident_id',
        'opened_at', 'assigned_at', 'en_route_at', 'on_scene_at', 'resolved_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => DispatchPriority::class,
            'status' => DispatchStatus::class,
            'incident_date' => 'date',
            'opened_at' => 'datetime',
            'assigned_at' => 'datetime',
            'en_route_at' => 'datetime',
            'on_scene_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function sosAlert(): BelongsTo
    {
        return $this->belongsTo(SosAlert::class);
    }

    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(DispatchActivityLog::class)->latest();
    }

    public function isActive(): bool
    {
        return ! in_array($this->status, [DispatchStatus::CLOSED, DispatchStatus::CANCELLED], true);
    }
}
