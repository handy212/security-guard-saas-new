<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Timesheet extends Model { use BelongsToTenant; protected $fillable=['tenant_id','guard_id','period_start','period_end','regular_hours','overtime_hours','gross_pay','status','approved_by_user_id','approved_at']; protected function casts(): array { return ['period_start'=>'date','period_end'=>'date','approved_at'=>'datetime']; } public function guard(): BelongsTo { return $this->belongsTo(Guard::class); } }
