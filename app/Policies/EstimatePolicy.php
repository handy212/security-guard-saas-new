<?php

namespace App\Policies;

use App\Models\Estimate;
use App\Models\User;

class EstimatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('billing.manage');
    }

    public function view(User $user, Estimate $estimate): bool
    {
        return $user->can('billing.manage') && $user->tenant_id === $estimate->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('billing.manage');
    }

    public function update(User $user, Estimate $estimate): bool
    {
        return $user->can('billing.manage') && $user->tenant_id === $estimate->tenant_id;
    }

    public function delete(User $user, Estimate $estimate): bool
    {
        return $user->can('billing.manage') && $user->tenant_id === $estimate->tenant_id;
    }
}
