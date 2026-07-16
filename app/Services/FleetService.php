<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\AssetCategory;
use App\Models\EquipmentAsset;
use App\Models\FleetVehicle;
use App\Models\Guard;
use App\Models\PatrolSession;
use App\Models\VehiclePatrol;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FleetService
{
    public function create(array $data): FleetVehicle
    {
        return DB::transaction(function () use ($data) {
            $vehicle = FleetVehicle::create($data);
            $this->syncEquipmentAsset($vehicle);

            return $vehicle;
        });
    }

    public function update(FleetVehicle $vehicle, array $data): FleetVehicle
    {
        return DB::transaction(function () use ($vehicle, $data) {
            $vehicle->update($data);
            $this->syncEquipmentAsset($vehicle->fresh());

            return $vehicle->fresh();
        });
    }

    public function delete(FleetVehicle $vehicle): void
    {
        if ($vehicle->vehiclePatrols()->where('status', 'active')->exists()) {
            throw new RuntimeException('Cannot delete a vehicle with an active trip.');
        }

        DB::transaction(function () use ($vehicle) {
            EquipmentAsset::where('fleet_vehicle_id', $vehicle->id)->update([
                'fleet_vehicle_id' => null,
                'status' => AssetStatus::RETIRED,
                'notes' => trim(($vehicle->notes ?? '').' Fleet unit removed.'),
            ]);
            $vehicle->delete();
        });
    }

    public function syncEquipmentAsset(FleetVehicle $vehicle): EquipmentAsset
    {
        $type = $vehicle->type instanceof VehicleType ? $vehicle->type->value : (string) $vehicle->type;
        $categoryName = config('assets.fleet_type_categories.'.$type, 'Vehicles');

        $category = AssetCategory::firstOrCreate(
            ['tenant_id' => $vehicle->tenant_id, 'name' => $categoryName],
            ['type' => 'serialized', 'description' => $categoryName.' used on deployments and patrols', 'is_active' => true]
        );

        $tag = 'FLT-'.$vehicle->plate_number;
        $name = $vehicle->name ?: ($vehicle->make.' '.$vehicle->model);
        $name = trim($name) !== '' ? trim($name) : $vehicle->plate_number;

        $statusEnum = $vehicle->status instanceof VehicleStatus
            ? $vehicle->status
            : (VehicleStatus::tryFrom((string) $vehicle->status) ?? VehicleStatus::AVAILABLE);

        $status = match ($statusEnum) {
            VehicleStatus::AVAILABLE => AssetStatus::AVAILABLE,
            VehicleStatus::IN_USE => AssetStatus::ISSUED,
            VehicleStatus::MAINTENANCE => AssetStatus::MAINTENANCE,
            VehicleStatus::RETIRED => AssetStatus::RETIRED,
        };

        $existing = EquipmentAsset::query()
            ->where('tenant_id', $vehicle->tenant_id)
            ->where('fleet_vehicle_id', $vehicle->id)
            ->first();

        // Do not free an asset that still has an open kit issue while syncing fleet metadata.
        if (
            $existing
            && $status === AssetStatus::AVAILABLE
            && $existing->assignments()->where('status', 'issued')->whereNull('returned_at')->exists()
        ) {
            $status = AssetStatus::ISSUED;
        }

        return EquipmentAsset::updateOrCreate(
            ['tenant_id' => $vehicle->tenant_id, 'fleet_vehicle_id' => $vehicle->id],
            [
                'asset_category_id' => $category->id,
                'site_id' => $vehicle->site_id,
                'asset_tag' => $tag,
                'name' => $name,
                'category' => $categoryName,
                'model' => $vehicle->model,
                'manufacturer' => $vehicle->make,
                'serial_number' => $vehicle->plate_number,
                'location' => $vehicle->site_id ? null : 'Fleet pool',
                'status' => $status,
                'condition' => 'good',
                'notes' => $vehicle->notes,
            ]
        );
    }

    public function startTrip(array $data): VehiclePatrol
    {
        $vehicle = FleetVehicle::findOrFail($data['vehicle_id']);

        if ($vehicle->status === VehicleStatus::RETIRED) {
            throw new RuntimeException('This vehicle is retired and cannot be assigned.');
        }

        if ($vehicle->status === VehicleStatus::MAINTENANCE) {
            throw new RuntimeException('This vehicle is in maintenance.');
        }

        if ($vehicle->vehiclePatrols()->where('status', 'active')->exists()) {
            throw new RuntimeException('This vehicle already has an active trip.');
        }

        $guard = ! empty($data['guard_id']) ? Guard::findOrFail($data['guard_id']) : null;
        $session = ! empty($data['patrol_session_id']) ? PatrolSession::findOrFail($data['patrol_session_id']) : null;

        $startOdo = $data['start_odometer'] ?? $vehicle->current_odometer;

        $trip = VehiclePatrol::create([
            'tenant_id' => $vehicle->tenant_id,
            'vehicle_id' => $vehicle->id,
            'guard_id' => $guard?->id,
            'patrol_session_id' => $session?->id,
            'vehicle_number' => $vehicle->plate_number,
            'driver_name' => $guard?->full_name ?? ($data['driver_name'] ?? null),
            'start_odometer' => $startOdo,
            'end_odometer' => null,
            'status' => 'active',
            'started_at' => now(),
            'fuel_log' => $this->fuelEntry($data),
        ]);

        $vehicle->update([
            'status' => VehicleStatus::IN_USE,
            'current_odometer' => $startOdo ?? $vehicle->current_odometer,
        ]);

        EquipmentAsset::where('fleet_vehicle_id', $vehicle->id)
            ->update(['status' => AssetStatus::ISSUED]);

        return $trip;
    }

    public function endTrip(VehiclePatrol $trip, array $data = []): VehiclePatrol
    {
        if (! $trip->isActive()) {
            throw new RuntimeException('Trip is already completed.');
        }

        $endOdo = $data['end_odometer'] ?? $trip->start_odometer;
        $fuelLog = $trip->fuel_log ?? [];

        if ($entry = $this->fuelEntry($data)) {
            $fuelLog = array_values(array_filter(array_merge(
                is_array($fuelLog) && array_is_list($fuelLog) ? $fuelLog : ($fuelLog ? [$fuelLog] : []),
                [$entry]
            )));
        }

        $trip->update([
            'end_odometer' => $endOdo,
            'status' => 'completed',
            'ended_at' => now(),
            'fuel_log' => $fuelLog ?: $trip->fuel_log,
            'patrol_session_id' => $data['patrol_session_id'] ?? $trip->patrol_session_id,
        ]);

        if ($trip->vehicle) {
            $hasOpenKit = EquipmentAsset::query()
                ->where('fleet_vehicle_id', $trip->vehicle->id)
                ->whereHas('assignments', fn ($q) => $q->where('status', 'issued')->whereNull('returned_at'))
                ->exists();

            if (! $hasOpenKit) {
                $trip->vehicle->update([
                    'status' => VehicleStatus::AVAILABLE,
                    'current_odometer' => $endOdo ?? $trip->vehicle->current_odometer,
                ]);

                EquipmentAsset::where('fleet_vehicle_id', $trip->vehicle->id)
                    ->update(['status' => AssetStatus::AVAILABLE]);
            } else {
                $trip->vehicle->update([
                    'current_odometer' => $endOdo ?? $trip->vehicle->current_odometer,
                ]);
            }
        }

        return $trip->fresh(['vehicle', 'assignedGuard', 'patrolSession']);
    }

    public function linkSession(VehiclePatrol $trip, PatrolSession $session): VehiclePatrol
    {
        $trip->update(['patrol_session_id' => $session->id]);

        return $trip->fresh();
    }

    private function fuelEntry(array $data): ?array
    {
        if (! filled($data['fuel_litres'] ?? null) && ! filled($data['fuel_cost'] ?? null)) {
            return null;
        }

        return [
            'litres' => filled($data['fuel_litres'] ?? null) ? (float) $data['fuel_litres'] : null,
            'cost' => filled($data['fuel_cost'] ?? null) ? (float) $data['fuel_cost'] : null,
            'at' => now()->toIso8601String(),
        ];
    }
}
