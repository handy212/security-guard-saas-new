<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sites.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('sites.manage');
    }

    public function update(User $user, Site $site): bool
    {
        return $user->can('sites.manage') && $user->tenant_id === $site->tenant_id;
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->can('sites.manage') && $user->tenant_id === $site->tenant_id;
    }
}
