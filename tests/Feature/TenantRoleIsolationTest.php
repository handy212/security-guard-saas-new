<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantRoleProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantRoleIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_same_role_name_is_isolated_per_tenant(): void
    {
        $tenantA = Tenant::create(['name' => 'Alpha Security', 'slug' => 'alpha', 'status' => 'active']);
        $tenantB = Tenant::create(['name' => 'Beta Security', 'slug' => 'beta', 'status' => 'active']);

        $roleA = Role::query()->where('tenant_id', $tenantA->id)->where('name', 'company-admin')->first();
        $roleB = Role::query()->where('tenant_id', $tenantB->id)->where('name', 'company-admin')->first();

        $this->assertNotNull($roleA);
        $this->assertNotNull($roleB);
        $this->assertNotSame($roleA->id, $roleB->id);
    }

    public function test_tenant_admin_role_changes_do_not_affect_other_tenants(): void
    {
        $tenantA = Tenant::create(['name' => 'Alpha Security', 'slug' => 'alpha', 'status' => 'active']);
        $tenantB = Tenant::create(['name' => 'Beta Security', 'slug' => 'beta', 'status' => 'active']);

        $roleA = Role::query()->where('tenant_id', $tenantA->id)->where('name', 'guard')->firstOrFail();
        $roleB = Role::query()->where('tenant_id', $tenantB->id)->where('name', 'guard')->firstOrFail();

        setPermissionsTeamId($tenantA->id);
        $roleA->syncPermissions(['mobile.use', 'dashboard.view']);

        setPermissionsTeamId($tenantB->id);
        $roleB->refresh();

        $this->assertTrue($roleB->hasPermissionTo('mobile.use'));
        $this->assertFalse($roleB->hasPermissionTo('dashboard.view'));
    }

    public function test_assign_role_uses_tenant_scoped_role(): void
    {
        $tenant = Tenant::create(['name' => 'Gamma Security', 'slug' => 'gamma', 'status' => 'active']);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Guard',
            'email' => 'guard@gamma.test',
            'password' => 'password',
            'status' => 'active',
        ]);

        app(TenantRoleProvisioner::class)->assignRole($user, 'guard');

        setPermissionsTeamId($tenant->id);
        $this->assertTrue($user->fresh()->hasRole('guard'));

        $otherTenant = Tenant::create(['name' => 'Delta Security', 'slug' => 'delta', 'status' => 'active']);
        setPermissionsTeamId($otherTenant->id);
        $this->assertFalse($user->fresh()->hasRole('guard'));
    }
}
