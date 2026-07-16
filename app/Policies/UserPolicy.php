<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function view(User $user, User $staffUser): bool
    {
        return $this->manageStaff($user, $staffUser);
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, User $staffUser): bool
    {
        return $this->manageStaff($user, $staffUser);
    }

    public function delete(User $user, User $staffUser): bool
    {
        return $this->manageStaff($user, $staffUser);
    }

    private function manageStaff(User $user, User $staffUser): bool
    {
        return $user->can('settings.manage')
            && $user->tenant_id === $staffUser->tenant_id
            && $staffUser->client_account_id === null;
    }
}
