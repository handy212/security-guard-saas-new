<?php

namespace App\Policies;

use App\Models\ClientComplaint;
use App\Models\User;

class ClientComplaintPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('clients.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('clients.manage');
    }

    public function update(User $user, ClientComplaint $complaint): bool
    {
        return $user->can('clients.manage') && $user->tenant_id === $complaint->tenant_id;
    }
}
