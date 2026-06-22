<?php

namespace App\Policies;

use App\Models\Incident;
use App\Models\User;

class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('incidents.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('incidents.manage') || $user->can('mobile.use');
    }

    public function approve(User $user, Incident $incident): bool
    {
        return $user->can('reports.approve') && $user->tenant_id === $incident->tenant_id;
    }

    public function close(User $user, Incident $incident): bool
    {
        return $user->can('incidents.manage') && $user->tenant_id === $incident->tenant_id;
    }
}
