<?php

namespace App\Livewire\Concerns;

trait AuthorizesModuleAccess
{
    protected function authorizePermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission), 403);
    }

    protected function authorizePolicy(string $ability, string $modelClass): void
    {
        $this->authorize($ability, $modelClass);
    }
}
