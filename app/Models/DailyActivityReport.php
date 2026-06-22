<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DailyActivityReport extends Model { use BelongsToTenant; protected $fillable=['tenant_id','site_id','guard_id','shift_assignment_id','title','report_date','summary','handover_notes','status','approved_by_user_id','approved_at']; protected function casts(): array { return ['report_date'=>'date','approved_at'=>'datetime']; } public function site(): BelongsTo { return $this->belongsTo(Site::class); } public function guard(): BelongsTo { return $this->belongsTo(Guard::class); } }
