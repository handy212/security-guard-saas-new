<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AnalyticsSnapshot extends Model
{
    use BelongsToTenant;
    protected $fillable=['tenant_id','snapshot_date','active_guards','active_sites','missed_patrols','incidents_by_severity','late_shifts','no_show_shifts','patrol_completion_rate','client_sla_performance','revenue_total','guard_scores']; protected $casts=['snapshot_date'=>'date','incidents_by_severity'=>'array','guard_scores'=>'array'];
}
