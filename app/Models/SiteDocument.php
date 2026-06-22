<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SiteDocument extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','site_id','title','document_type','file_path','expires_on','client_visible']; protected $casts=['expires_on'=>'date','client_visible'=>'boolean'];
    public function site() { return $this->belongsTo(Site::class); }
}
