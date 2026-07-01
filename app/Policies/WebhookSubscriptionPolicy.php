<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WebhookSubscription;

class WebhookSubscriptionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage');
    }

    public function delete(User $user, WebhookSubscription $subscription): bool
    {
        return $user->can('settings.manage') && $user->tenant_id === $subscription->tenant_id;
    }
}
