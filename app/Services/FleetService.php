<?php

namespace App\Services;

use App\Enums\VehicleStatus;
use App\Models\FleetVehicle;
use App\Models\Guard;
use App\Models\PatrolSession;
use App\Models\VehiclePatrol;
use RuntimeException;

class FleetService
{
    public function create(array $data): FleetVehicle
    {
        return FleetVehicle::create($data);
    }

    public function update(FleetVehicle $vehicle, array $data): FleetVehicle
    {
        $vehicle->update($data);

        return $vehicle->fresh();
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
            $trip->vehicle->update([
                'status' => VehicleStatus::AVAILABLE,
                'current_odometer' => $endOdo ?? $trip->vehicle->current_odometer,
            ]);
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
