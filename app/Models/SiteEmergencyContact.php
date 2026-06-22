<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SiteEmergencyContact extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','site_id','name','role','phone','email','priority'];
    public function site() { return $this->belongsTo(Site::class); }
}
