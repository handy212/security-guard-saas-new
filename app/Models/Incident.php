<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Incident extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'site_id', 'shift_assignment_id', 'reported_by_user_id', 'approved_by_user_id',
        'title', 'type', 'incident_type', 'severity', 'description', 'status',
        'latitude', 'longitude', 'reported_at', 'occurred_at', 'approved_at', 'closed_at', 'resolution',
    ];

    protected function casts(): array
    {
        return [
            'reported_at' => 'datetime',
            'occurred_at' => 'datetime',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(IncidentMedia::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function isOpen(): bool
    {
        return ! in_array($this->status, ['closed', 'rejected'], true);
    }
}
