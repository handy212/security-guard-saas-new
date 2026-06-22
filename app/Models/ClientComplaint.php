<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class ClientComplaint extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','client_account_id','site_id','subject','description','priority','status','assigned_to','resolved_at']; protected $casts=['resolved_at'=>'datetime'];
}
