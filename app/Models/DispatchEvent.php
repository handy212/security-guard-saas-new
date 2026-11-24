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
        'patriot_job_id', 'patriot_client_ref', 'dispatch_team_id', 'returned_at', 'acknowledged_at',
        'patriot_instance', 'patriot_sync_status', 'patriot_intake_snapshot', 'patriot_payload', 'patriot_response_state', 'patriot_pending_action', 'patriot_writeback_started_at', 'patriot_writeback_error',
        'opened_at', 'assigned_at', 'en_route_at', 'on_scene_at', 'resolved_at', 'closed_at',
    ];

    protected $hidden = ['patriot_payload', 'patriot_intake_snapshot', 'patriot_instance'];

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
            'returned_at' => 'datetime',
            'acknowledged_at' => 'datetime',
            'patriot_writeback_started_at' => 'datetime',
            'patriot_payload' => 'array',
            'patriot_intake_snapshot' => 'array',
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

    public function dispatchTeam(): BelongsTo
    {
        return $this->belongsTo(DispatchTeam::class);
    }

    public function mayAssignFromPatriot(): bool
    {
        return $this->patriot_job_id === null
            || ($this->hasPatriotResponseLocation() && $this->status === DispatchStatus::OPEN
                && $this->patriot_pending_action === null && $this->patriot_writeback_error === null
                && in_array($this->patriot_response_state, [null, 'accepted'], true));
    }

    public function hasPatriotResponseLocation(): bool
    {
        if ($this->patriot_instance === null) {
            return $this->site_id !== null;
        }

        $location = $this->patriot_payload['location'] ?? [];

        return filled($this->incident_location) || filled($location['address'] ?? null)
            || (isset($location['latitude'], $location['longitude']))
            || (isset($this->latitude, $this->longitude))
            || ($this->site && (filled($this->site->address) || isset($this->site->latitude, $this->site->longitude)));
    }

    public function awaitingStationReturn(): bool
    {
        return $this->dispatch_team_id !== null && $this->returned_at === null
            && in_array($this->status, [DispatchStatus::RESOLVED, DispatchStatus::CLOSED, DispatchStatus::CANCELLED], true);
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
