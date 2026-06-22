<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TenantSetting extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','key','value']; protected $casts=['value'=>'array'];
}
