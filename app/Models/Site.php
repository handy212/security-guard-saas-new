<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'client_account_id', 'supervisor_user_id', 'name', 'address', 'latitude', 'longitude',
        'geofence_radius_meters', 'status', 'instructions', 'settings',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'settings' => 'array',
        ];
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(SitePost::class);
    }

    public function postOrders(): HasMany
    {
        return $this->hasMany(PostOrder::class);
    }

    public function patrolRoutes(): HasMany
    {
        return $this->hasMany(PatrolRoute::class);
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(SiteEmergencyContact::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(SiteDocument::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(SiteNote::class);
    }

    public function slaRequirements(): HasMany
    {
        return $this->hasMany(SiteSlaRequirement::class);
    }

    public function reportSchedules(): HasMany
    {
        return $this->hasMany(SiteReportSchedule::class);
    }

    public function reportAssignments(): HasMany
    {
        return $this->hasMany(ReportTemplateAssignment::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    public function resolvedSettings(): array
    {
        return array_merge(
            config('site_profile.default_settings', []),
            $this->settings ?? [],
        );
    }
}
