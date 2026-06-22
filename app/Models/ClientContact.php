<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'client_account_id',
        'name',
        'email',
        'phone',
        'role'
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

    public function tenant() { return $this->belongsTo(Tenant::class); }

    public function clientAccount() { return $this->belongsTo(ClientAccount::class); }
}
