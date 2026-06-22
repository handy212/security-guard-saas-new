<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CheckpointScan extends Model { use BelongsToTenant; protected $fillable=['tenant_id','patrol_session_id','patrol_checkpoint_id','guard_id','scanned_at','latitude','longitude','notes','status']; protected function casts(): array { return ['scanned_at'=>'datetime']; } public function session(): BelongsTo { return $this->belongsTo(PatrolSession::class,'patrol_session_id'); } public function checkpoint(): BelongsTo { return $this->belongsTo(PatrolCheckpoint::class,'patrol_checkpoint_id'); } public function guard(): BelongsTo { return $this->belongsTo(Guard::class); } }
