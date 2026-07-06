<?php

namespace App\Models;

use App\Enums\ShiftStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Support\EnumHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Shift extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'client_account_id', 'site_id', 'site_post_id', 'title',
        'starts_at', 'ends_at', 'required_guards', 'billing_rate', 'billable_hours', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'billing_rate' => 'decimal:2',
            'billable_hours' => 'decimal:2',
            'status' => ShiftStatus::class,
        ];
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function sitePost(): BelongsTo
    {
        return $this->belongsTo(SitePost::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function openShiftBids(): HasMany
    {
        return $this->hasMany(OpenShiftBid::class);
    }

    public function activeAssignmentsCount(): int
    {
        return $this->activeAssignments()->count();
    }

    public function activeAssignments(): Collection
    {
        if (! $this->relationLoaded('assignments')) {
            return $this->assignments()
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->get();
        }

        return $this->assignments
            ->filter(fn (ShiftAssignment $assignment) => EnumHelper::isNotOneOf($assignment->status, ['cancelled', 'completed']))
            ->values();
    }
}
