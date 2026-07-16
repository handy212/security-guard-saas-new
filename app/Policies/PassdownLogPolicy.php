<?php

namespace App\Policies;

use App\Models\PassdownLog;
use App\Models\User;

class PassdownLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('patrols.manage');
    }

    public function view(User $user, PassdownLog $passdownLog): bool
    {
        return $user->can('patrols.manage') && $user->tenant_id === $passdownLog->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('patrols.manage');
    }

    public function update(User $user, PassdownLog $passdownLog): bool
    {
        return $user->can('patrols.manage') && $user->tenant_id === $passdownLog->tenant_id;
    }

    public function delete(User $user, PassdownLog $passdownLog): bool
    {
        return $user->can('patrols.manage') && $user->tenant_id === $passdownLog->tenant_id;
    }
}
