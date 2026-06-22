<?php

namespace App\Policies;

use App\Models\Shift;
use App\Models\User;

class ShiftPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('schedules.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('schedules.manage');
    }

    public function assign(User $user, Shift $shift): bool
    {
        return $user->can('schedules.manage') && $user->tenant_id === $shift->tenant_id;
    }
}
