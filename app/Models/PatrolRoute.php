<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class PatrolRoute extends Model { use BelongsToTenant; protected $fillable=['tenant_id','site_id','name','description','expected_duration_minutes','status']; public function site(): BelongsTo { return $this->belongsTo(Site::class); } public function checkpoints(): HasMany { return $this->hasMany(PatrolCheckpoint::class); } }
