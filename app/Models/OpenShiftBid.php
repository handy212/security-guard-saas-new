<?php

namespace App\Models;

use App\Enums\BidStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OpenShiftBid extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'shift_id', 'guard_id', 'notes', 'status'];

    protected function casts(): array
    {
        return ['status' => BidStatus::class];
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function assignedGuard()
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }
}
