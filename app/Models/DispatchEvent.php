<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DispatchEvent extends Model { use BelongsToTenant; protected $fillable=['tenant_id','site_id','incident_id','title','priority','status','opened_at','closed_at','notes']; protected function casts(): array { return ['opened_at'=>'datetime','closed_at'=>'datetime']; } public function site(): BelongsTo { return $this->belongsTo(Site::class); } }
