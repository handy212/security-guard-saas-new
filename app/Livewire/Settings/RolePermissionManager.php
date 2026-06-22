<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionManager extends Component
{
    public string $roleName = '';

    public array $permissions = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
    }

    public function createRole(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        Role::firstOrCreate(['name' => $this->roleName, 'guard_name' => 'web']);
        $this->roleName = '';
    }

    public function sync(Role $role): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $role->syncPermissions($this->permissions[$role->id] ?? []);
    }

    public function render()
    {
        return view('livewire.settings.role-permission-manager', [
            'roles' => Role::with('permissions')->get(),
            'allPermissions' => Permission::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
