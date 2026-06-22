<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PatrolCheckpoint extends Model { use BelongsToTenant; protected $fillable=['tenant_id','patrol_route_id','name','code','sequence','latitude','longitude','instructions','status']; public function route(): BelongsTo { return $this->belongsTo(PatrolRoute::class,'patrol_route_id'); } }
