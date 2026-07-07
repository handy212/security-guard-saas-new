<?php

namespace App\Livewire\Settings;

use App\Support\TenantContext;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionManager extends Component
{
    public string $roleName = '';

    public array $permissions = [];

    /** Roles tenant admins may view or edit. */
    private const HIDDEN_ROLES = ['super-admin'];

    /** Permissions tenant admins may never assign. */
    private const HIDDEN_PERMISSIONS = ['tenants.manage'];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        abort_if(TenantContext::isPlatformConsole(), 403);

        foreach ($this->tenantRoles() as $role) {
            $this->permissions[$role->id] = $role->permissions->pluck('name')->toArray();
        }
    }

    public function createRole(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);

        $name = strtolower(trim($this->roleName));

        abort_if(in_array($name, self::HIDDEN_ROLES, true), 403, 'This role name is reserved.');

        Role::firstOrCreate([
            'name' => $name,
            'guard_name' => 'web',
            'tenant_id' => TenantContext::id(),
        ]);
        $this->roleName = '';
        session()->flash('status', 'Role created.');
    }

    public function sync(Role $role): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        abort_if(in_array($role->name, self::HIDDEN_ROLES, true), 403);
        abort_if($role->tenant_id !== TenantContext::id(), 403);

        $allowed = collect($this->permissions[$role->id] ?? [])
            ->reject(fn (string $permission) => in_array($permission, self::HIDDEN_PERMISSIONS, true))
            ->values()
            ->all();

        $role->syncPermissions($allowed);
        session()->flash('status', 'Permissions updated.');
    }

    public function render()
    {
        return view('livewire.settings.role-permission-manager', [
            'roles' => $this->tenantRoles(),
            'allPermissions' => $this->tenantPermissions(),
        ])->layout('layouts.app');
    }

    private function tenantRoles()
    {
        return Role::with('permissions')
            ->where('tenant_id', TenantContext::id())
            ->whereNotIn('name', self::HIDDEN_ROLES)
            ->orderBy('name')
            ->get();
    }

    private function tenantPermissions()
    {
        return Permission::query()
            ->whereNotIn('name', self::HIDDEN_PERMISSIONS)
            ->orderBy('name')
            ->get();
    }
}
