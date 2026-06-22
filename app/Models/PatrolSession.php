<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class PatrolSession extends Model { use BelongsToTenant; protected $fillable=['tenant_id','patrol_route_id','guard_id','shift_assignment_id','status','started_at','completed_at','notes']; protected function casts(): array { return ['started_at'=>'datetime','completed_at'=>'datetime']; } public function route(): BelongsTo { return $this->belongsTo(PatrolRoute::class,'patrol_route_id'); } public function guard(): BelongsTo { return $this->belongsTo(Guard::class); } public function scans(): HasMany { return $this->hasMany(CheckpointScan::class); } }
