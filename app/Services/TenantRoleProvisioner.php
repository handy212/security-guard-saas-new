<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantRoleProvisioner
{
    public const PLATFORM_TENANT_ID = 0;

    public function provision(Tenant $tenant): void
    {
        setPermissionsTeamId($tenant->id);

        foreach (config('tenant_roles.roles', []) as $name => $permissions) {
            $role = Role::query()->firstOrCreate(
                [
                    'name' => $name,
                    'guard_name' => 'web',
                    'tenant_id' => $tenant->id,
                ],
            );

            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function provisionAllTenants(): void
    {
        Tenant::query()->each(fn (Tenant $tenant) => $this->provision($tenant));
    }

    public function ensurePlatformRoles(): void
    {
        setPermissionsTeamId(self::PLATFORM_TENANT_ID);

        foreach (config('tenant_roles.platform_roles', ['super-admin']) as $name) {
            $permissions = Permission::query()->pluck('name')->all();

            $role = Role::query()->firstOrCreate(
                [
                    'name' => $name,
                    'guard_name' => 'web',
                    'tenant_id' => self::PLATFORM_TENANT_ID,
                ],
            );

            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function assignRole(User $user, string $roleName): void
    {
        if ($user->tenant_id === null) {
            setPermissionsTeamId(self::PLATFORM_TENANT_ID);
            $user->assignRole($roleName);

            return;
        }

        $this->provision(Tenant::findOrFail($user->tenant_id));

        setPermissionsTeamId($user->tenant_id);
        $user->assignRole($roleName);
    }

    /**
     * Migrate legacy global roles (pre-teams) into per-tenant roles.
     */
    public function migrateLegacyAssignments(): void
    {
        $pivotTable = config('permission.table_names.model_has_roles');
        $pivotRole = config('permission.column_names.role_pivot_key') ?? 'role_id';

        $legacyRoles = Role::query()
            ->where('tenant_id', self::PLATFORM_TENANT_ID)
            ->whereNotIn('name', config('tenant_roles.platform_roles', []))
            ->get()
            ->keyBy('name');

        if ($legacyRoles->isEmpty()) {
            return;
        }

        $this->provisionAllTenants();

        $assignments = DB::table($pivotTable)
            ->where('model_type', User::class)
            ->get();

        foreach ($assignments as $assignment) {
            $user = User::find($assignment->model_id);
            if (! $user?->tenant_id) {
                continue;
            }

            $legacyRoleName = Role::query()->where('id', $assignment->{$pivotRole})->value('name');
            if (! $legacyRoles->has($legacyRoleName)) {
                continue;
            }

            DB::table($pivotTable)
                ->where($pivotRole, $assignment->{$pivotRole})
                ->where('model_id', $assignment->model_id)
                ->where('model_type', $assignment->model_type)
                ->delete();

            $this->assignRole($user, $legacyRoleName);
        }

        Role::query()
            ->where('tenant_id', self::PLATFORM_TENANT_ID)
            ->whereNotIn('name', config('tenant_roles.platform_roles', []))
            ->each(function (Role $role): void {
                $role->permissions()->detach();
                $role->delete();
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
