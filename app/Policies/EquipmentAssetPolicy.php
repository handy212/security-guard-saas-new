<?php

namespace App\Policies;

use App\Models\EquipmentAsset;
use App\Models\User;

class EquipmentAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('equipment.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('equipment.manage');
    }

    public function update(User $user, EquipmentAsset $asset): bool
    {
        return $user->can('equipment.manage') && $user->tenant_id === $asset->tenant_id;
    }

    public function delete(User $user, EquipmentAsset $asset): bool
    {
        return $user->can('equipment.manage') && $user->tenant_id === $asset->tenant_id;
    }
}
