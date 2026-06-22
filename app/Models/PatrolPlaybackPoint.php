<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PatrolPlaybackPoint extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','patrol_session_id','latitude','longitude','accuracy_meters','recorded_at']; protected $casts=['recorded_at'=>'datetime'];
}
