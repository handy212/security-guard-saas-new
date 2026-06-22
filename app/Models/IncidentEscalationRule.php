<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class IncidentEscalationRule extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','incident_type','severity','notify_after_minutes','notify_roles','notify_client','is_active']; protected $casts=['notify_roles'=>'array','notify_client'=>'boolean','is_active'=>'boolean'];
}
