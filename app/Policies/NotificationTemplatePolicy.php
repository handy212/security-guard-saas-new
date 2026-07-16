<?php

namespace App\Policies;

use App\Models\NotificationTemplate;
use App\Models\User;

class NotificationTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function view(User $user, NotificationTemplate $notificationTemplate): bool
    {
        return $user->can('settings.manage') && $user->tenant_id === $notificationTemplate->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function update(User $user, NotificationTemplate $notificationTemplate): bool
    {
        return $user->can('settings.manage') && $user->tenant_id === $notificationTemplate->tenant_id;
    }

    public function delete(User $user, NotificationTemplate $notificationTemplate): bool
    {
        return $user->can('settings.manage') && $user->tenant_id === $notificationTemplate->tenant_id;
    }
}
