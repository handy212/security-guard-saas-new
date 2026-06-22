<?php

namespace App\Policies;

use App\Models\ClientAccount;
use App\Models\User;

class ClientAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('clients.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('clients.manage');
    }

    public function update(User $user, ClientAccount $clientAccount): bool
    {
        return $user->can('clients.manage') && $user->tenant_id === $clientAccount->tenant_id;
    }

    public function delete(User $user, ClientAccount $clientAccount): bool
    {
        return $user->can('clients.manage') && $user->tenant_id === $clientAccount->tenant_id;
    }
}
