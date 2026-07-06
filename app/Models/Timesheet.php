<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timesheet extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'guard_id', 'period_start', 'period_end',
        'regular_hours', 'overtime_hours', 'gross_pay', 'status',
        'approved_by_user_id', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'approved_at' => 'datetime',
            'regular_hours' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'gross_pay' => 'decimal:2',
        ];
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
