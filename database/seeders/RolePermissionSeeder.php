<?php

namespace Database\Seeders;

use App\Services\TenantRoleProvisioner;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'dashboard.view', 'clients.manage', 'sites.manage', 'guards.manage', 'schedules.manage',
            'attendance.manage', 'patrols.manage', 'incidents.manage', 'reports.approve', 'dispatch.manage',
            'billing.manage', 'payroll.manage', 'settings.manage', 'audit.view', 'client_portal.view', 'mobile.use',
            'tenants.manage', 'analytics.view', 'compliance.manage', 'equipment.manage', 'visitors.manage',
            'exports.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        app(TenantRoleProvisioner::class)->ensurePlatformRoles();
    }
}
