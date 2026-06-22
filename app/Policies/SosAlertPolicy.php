<?php

namespace App\Policies;

use App\Models\SosAlert;
use App\Models\User;

class SosAlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('dispatch.manage');
    }

    public function acknowledge(User $user, SosAlert $alert): bool
    {
        return $user->can('dispatch.manage') && $user->tenant_id === $alert->tenant_id;
    }
}
