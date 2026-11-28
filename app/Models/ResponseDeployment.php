<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResponseDeployment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'dispatch_team_id', 'standby_point_id', 'standby_location', 'officer_ids',
        'custodian_id', 'handover_location', 'deployed_by', 'arrived_by', 'closed_by',
        'planned_end_at', 'arrived_at', 'ending_at', 'closed_at', 'arrival_override',
    ];

    protected function casts(): array
    {
        return [
            'standby_location' => 'array', 'officer_ids' => 'array', 'planned_end_at' => 'datetime',
            'arrived_at' => 'datetime', 'ending_at' => 'datetime', 'closed_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(DispatchTeam::class, 'dispatch_team_id')->withTrashed();
    }

    public function custodian(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'custodian_id');
    }

    public function kit(): HasMany
    {
        return $this->hasMany(EquipmentAssignment::class);
    }
}
