<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class TrainingRecord extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','guard_id','course_name','provider','completed_on','expires_on','certificate_path','status']; protected $casts=['completed_on'=>'date','expires_on'=>'date'];
}
