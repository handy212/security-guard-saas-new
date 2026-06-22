<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class DisciplinaryRecord extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','guard_id','case_number','type','description','action_taken','occurred_on','recorded_by']; protected $casts=['occurred_on'=>'date'];
}
