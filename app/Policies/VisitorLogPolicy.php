<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisitorLog;

class VisitorLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('visitors.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('visitors.manage');
    }

    public function update(User $user, VisitorLog $visitorLog): bool
    {
        return $user->can('visitors.manage') && $user->tenant_id === $visitorLog->tenant_id;
    }
}
