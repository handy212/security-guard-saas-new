<?php

namespace App\Policies;

use App\Models\DispatchEvent;
use App\Models\User;

class DispatchEventPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('dispatch.manage');
    }

    public function update(User $user, DispatchEvent $event): bool
    {
        return $user->can('dispatch.manage') && $user->tenant_id === $event->tenant_id;
    }
}
