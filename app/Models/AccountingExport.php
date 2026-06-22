<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AccountingExport extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','provider','export_type','status','file_path','payload','exported_at']; protected $casts=['payload'=>'array','exported_at'=>'datetime'];
}
