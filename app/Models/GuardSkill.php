<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class GuardSkill extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','guard_id','skill','level'];
}
