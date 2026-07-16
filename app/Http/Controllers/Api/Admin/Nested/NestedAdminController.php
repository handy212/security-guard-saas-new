<?php

namespace App\Http\Controllers\Api\Admin\Nested;

use App\Http\Controllers\Api\Admin\AdminController;
use App\Models\ClientAccount;
use App\Models\Guard;
use App\Models\Site;

abstract class NestedAdminController extends AdminController
{
    protected function belongsToSite(Site $site, object $child, string $key = 'site_id'): void
    {
        abort_unless((int) $child->{$key} === (int) $site->id, 404);
    }

    protected function belongsToClient(ClientAccount $client, object $child, string $key = 'client_account_id'): void
    {
        abort_unless((int) $child->{$key} === (int) $client->id, 404);
    }

    protected function belongsToGuard(Guard $guard, object $child, string $key = 'guard_id'): void
    {
        abort_unless((int) $child->{$key} === (int) $guard->id, 404);
    }
}
