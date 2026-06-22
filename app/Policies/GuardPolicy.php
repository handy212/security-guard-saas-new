<?php

namespace App\Policies;

use App\Models\Guard;
use App\Models\User;

class GuardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('guards.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('guards.manage');
    }

    public function update(User $user, Guard $guard): bool
    {
        return $user->can('guards.manage') && $user->tenant_id === $guard->tenant_id;
    }

    public function delete(User $user, Guard $guard): bool
    {
        return $user->can('guards.manage') && $user->tenant_id === $guard->tenant_id;
    }
}
