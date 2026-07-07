<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientAccount extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'industry', 'email', 'phone', 'address', 'latitude', 'longitude',
        'status', 'default_hourly_rate', 'portal_enabled', 'portal_welcome_message',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'default_hourly_rate' => 'decimal:2',
            'portal_enabled' => 'boolean',
        ];
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ClientNote::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ClientDocument::class);
    }

    public function reportSchedules(): HasMany
    {
        return $this->hasMany(ClientReportSchedule::class);
    }

    public function portalUsers(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function shifts(): HasMany
    {
        return $this->hasMany(Shift::class);
    }

    public function complaints(): HasMany
    {
        return $this->hasMany(ClientComplaint::class);
    }
}
