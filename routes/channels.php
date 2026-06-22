<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('tenant.{tenantId}.dispatch', function ($user, int $tenantId) {
    return (int) $user->tenant_id === $tenantId && $user->can('dispatch.manage');
});
