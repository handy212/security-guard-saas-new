<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class BreakLog extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','attendance_log_id','started_at','ended_at','type']; protected $casts=['started_at'=>'datetime','ended_at'=>'datetime'];
}
