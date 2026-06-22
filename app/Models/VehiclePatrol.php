<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class VehiclePatrol extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','patrol_session_id','vehicle_number','driver_name','start_odometer','end_odometer','fuel_log']; protected $casts=['fuel_log'=>'array'];
}
