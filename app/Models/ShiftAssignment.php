<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant; use Illuminate\Database\Eloquent\Model;
use App\Models\ShiftSwapRequest; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ShiftAssignment extends Model { use BelongsToTenant; protected $fillable=['tenant_id','shift_id','guard_id','status','assigned_at','confirmed_at','notes']; protected function casts(): array { return ['assigned_at'=>'datetime','confirmed_at'=>'datetime']; } public function shift(): BelongsTo { return $this->belongsTo(Shift::class); } public function guard(): BelongsTo { return $this->belongsTo(Guard::class); } }
