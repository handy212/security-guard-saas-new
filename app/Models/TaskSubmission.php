<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class TaskSubmission extends Model { use BelongsToTenant; protected $guarded=[]; protected $casts=['media'=>'array']; public function scan(){return $this->belongsTo(CheckpointScan::class,'checkpoint_scan_id');} public function task(){return $this->belongsTo(CheckpointTask::class,'checkpoint_task_id');} }
