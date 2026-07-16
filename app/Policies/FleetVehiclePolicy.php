<?php

namespace App\Policies;

use App\Models\FleetVehicle;
use App\Models\User;

class FleetVehiclePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('patrols.manage');
    }

    public function view(User $user, FleetVehicle $fleetVehicle): bool
    {
        return $user->can('patrols.manage') && $user->tenant_id === $fleetVehicle->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('patrols.manage');
    }

    public function update(User $user, FleetVehicle $fleetVehicle): bool
    {
        return $user->can('patrols.manage') && $user->tenant_id === $fleetVehicle->tenant_id;
    }

    public function delete(User $user, FleetVehicle $fleetVehicle): bool
    {
        return $user->can('patrols.manage') && $user->tenant_id === $fleetVehicle->tenant_id;
    }
}
