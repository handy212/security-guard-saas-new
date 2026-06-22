<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class OpenShiftBid extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','shift_id','guard_id','notes','status'];
}
