<?php

namespace App\Policies;

use App\Models\PatrolSession;
use App\Models\User;

class PatrolSessionPolicy
{
    public function view(User $user, PatrolSession $session): bool
    {
        return $user->can('patrols.manage') && $user->tenant_id === $session->tenant_id;
    }
}
