<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard.view', 'clients.manage', 'sites.manage', 'guards.manage', 'schedules.manage',
            'attendance.manage', 'patrols.manage', 'incidents.manage', 'reports.approve', 'dispatch.manage',
            'billing.manage', 'payroll.manage', 'settings.manage', 'client_portal.view', 'mobile.use',
            'tenants.manage', 'analytics.view', 'compliance.manage', 'equipment.manage', 'visitors.manage',
            'exports.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $roles = [
            'super-admin' => $permissions,
            'company-admin' => array_diff($permissions, ['tenants.manage']),
            'operations-manager' => [
                'dashboard.view', 'clients.manage', 'sites.manage', 'guards.manage', 'schedules.manage',
                'attendance.manage', 'patrols.manage', 'incidents.manage', 'reports.approve', 'dispatch.manage',
                'analytics.view', 'compliance.manage', 'equipment.manage', 'visitors.manage',
            ],
            'supervisor' => [
                'dashboard.view', 'attendance.manage', 'patrols.manage', 'incidents.manage',
                'reports.approve', 'dispatch.manage',
            ],
            'guard' => ['mobile.use'],
            'client' => ['client_portal.view'],
            'finance' => ['dashboard.view', 'billing.manage', 'payroll.manage', 'exports.manage', 'analytics.view'],
        ];

        foreach ($roles as $name => $perms) {
            $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }
    }
}
