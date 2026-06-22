<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class DataRetentionPolicy extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','record_type','retention_days','legal_hold']; protected $casts=['legal_hold'=>'boolean'];
}
