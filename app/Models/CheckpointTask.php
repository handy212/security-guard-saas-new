<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class CheckpointTask extends Model { use BelongsToTenant; protected $guarded=[]; public function checkpoint(){return $this->belongsTo(PatrolCheckpoint::class,'patrol_checkpoint_id');} }
