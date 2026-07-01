<?php

namespace App\Livewire\Concerns;

use App\Support\TenantContext;

trait ManagesPlatform
{
    protected function ensurePlatformAdmin(): void
    {
        abort_unless(TenantContext::isPlatformAdmin(), 403);
    }
}
