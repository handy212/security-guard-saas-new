<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'plan_id',
        'trial_ends_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_required' => 'boolean',
        'is_geofence_valid' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'recorded_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'occurred_at' => 'datetime',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'triggered_at' => 'datetime',
        'resolved_at' => 'datetime',
        'issued_at' => 'date',
        'expires_at' => 'date',
        'issue_date' => 'date',
        'due_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
    ];
}
