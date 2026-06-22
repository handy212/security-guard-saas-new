<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SosAlert extends Model { use BelongsToTenant; protected $fillable=['tenant_id','guard_id','site_id','latitude','longitude','message','status','raised_at','acknowledged_by_user_id','acknowledged_at']; protected function casts(): array { return ['raised_at'=>'datetime','acknowledged_at'=>'datetime']; } public function assignedGuard(): BelongsTo { return $this->belongsTo(Guard::class); } public function site(): BelongsTo { return $this->belongsTo(Site::class); } }
