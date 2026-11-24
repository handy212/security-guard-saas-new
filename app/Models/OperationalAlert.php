<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalAlert extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'event_type', 'source_reference', 'severity', 'priority', 'title', 'instructions',
        'action_url', 'context', 'acknowledge_by', 'acknowledged_by', 'acknowledged_at', 'escalated_at', 'escalation_failed_at', 'resolved_at',
        'backup_escalate_at', 'backup_escalated_at', 'backup_escalation_failed_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'acknowledge_by' => 'datetime',
            'acknowledged_at' => 'datetime',
            'escalated_at' => 'datetime',
            'escalation_failed_at' => 'datetime',
            'backup_escalate_at' => 'datetime',
            'backup_escalated_at' => 'datetime',
            'backup_escalation_failed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }
}
