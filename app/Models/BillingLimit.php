<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class BillingLimit extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','max_guards','max_sites','max_clients','storage_mb'];
}
