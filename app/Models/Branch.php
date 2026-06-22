<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','name','code','phone','email','address','city','country','is_active']; protected $casts=['is_active'=>'boolean'];
}
