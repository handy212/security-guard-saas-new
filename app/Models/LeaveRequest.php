<?php

namespace App\Models;

use App\Enums\LeaveStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'guard_id', 'starts_on', 'ends_on', 'type', 'reason',
        'status', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'approved_at' => 'datetime',
            'status' => LeaveStatus::class,
        ];
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
