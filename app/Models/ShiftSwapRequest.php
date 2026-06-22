<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ShiftSwapRequest extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','shift_assignment_id','requested_by_guard_id','replacement_guard_id','reason','status','approved_by','approved_at']; protected $casts=['approved_at'=>'datetime'];
}
