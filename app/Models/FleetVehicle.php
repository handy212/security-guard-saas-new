<?php

namespace App\Models;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class FleetVehicle extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'site_id', 'type', 'plate_number', 'name', 'make', 'model',
        'status', 'current_odometer', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'status' => VehicleStatus::class,
            'current_odometer' => 'integer',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function vehiclePatrols(): HasMany
    {
        return $this->hasMany(VehiclePatrol::class, 'vehicle_id');
    }

    public function equipmentAsset(): HasOne
    {
        return $this->hasOne(EquipmentAsset::class, 'fleet_vehicle_id');
    }

    public function displayName(): string
    {
        $label = $this->name ?: $this->plate_number;

        return trim($label.' · '.$this->plate_number.($this->type ? ' ('.$this->type->label().')' : ''));
    }

    public function isAvailable(): bool
    {
        return $this->status === VehicleStatus::AVAILABLE;
    }
}
