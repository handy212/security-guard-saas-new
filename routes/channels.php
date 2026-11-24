<?php

use App\Services\NotificationDispatcher;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tenant.{tenantId}.dispatch', function ($user, int $tenantId) {
    return (int) $user->tenant_id === $tenantId && NotificationDispatcher::canAccessDispatchChannel($user);
});

Broadcast::channel('tenant.{tenantId}.messenger.{threadId}', function ($user, int $tenantId, int $threadId) {
    return (int) $user->tenant_id === $tenantId;
});

Broadcast::channel('user.{userId}', fn ($user, int $userId): bool => $user->id === $userId && $user->status === 'active');
