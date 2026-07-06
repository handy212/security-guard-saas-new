<?php

namespace App\Models;

use App\Enums\SwapStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ShiftSwapRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'shift_assignment_id', 'requested_by_guard_id', 'replacement_guard_id', 'reason', 'status', 'approved_by', 'approved_at'];

    protected $casts = ['approved_at' => 'datetime', 'status' => SwapStatus::class];

    public function requestedByGuard()
    {
        return $this->belongsTo(Guard::class, 'requested_by_guard_id');
    }

    public function replacementGuard()
    {
        return $this->belongsTo(Guard::class, 'replacement_guard_id');
    }

    public function shiftAssignment()
    {
        return $this->belongsTo(ShiftAssignment::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
