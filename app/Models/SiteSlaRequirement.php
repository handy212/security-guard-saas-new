<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SiteSlaRequirement extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','site_id','metric','target_value','frequency','grace_minutes','is_active']; protected $casts=['is_active'=>'boolean'];
}
