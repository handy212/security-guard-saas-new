<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class LeaveRequest extends Model { use BelongsToTenant; protected $guarded=[]; protected $casts=['starts_on'=>'date','ends_on'=>'date','approved_at'=>'datetime']; public function assignedGuard(){return $this->belongsTo(Guard::class);} public function approver(){return $this->belongsTo(User::class,'approved_by');} }
