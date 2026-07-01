<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\UserHomeRouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserHomeRouteServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_guard_role_lands_on_field_app(): void
    {
        $tenant = Tenant::create(['name' => 'Field Co', 'slug' => 'field-co', 'status' => 'active']);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Field Guard',
            'email' => 'field-guard@test.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $user->assignRole('guard');

        $this->assertSame(route('guard.mobile'), app(UserHomeRouteService::class)->resolve($user));
    }

    public function test_client_role_lands_on_client_portal(): void
    {
        $tenant = Tenant::create(['name' => 'Client Co', 'slug' => 'client-co', 'status' => 'active']);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Client User',
            'email' => 'client-user@test.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $user->assignRole('client');

        $this->assertSame(route('client-portal.dashboard'), app(UserHomeRouteService::class)->resolve($user));
    }

    public function test_supervisor_lands_on_dispatch(): void
    {
        $tenant = Tenant::create(['name' => 'Dispatch Co', 'slug' => 'dispatch-co', 'status' => 'active']);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Supervisor',
            'email' => 'supervisor@test.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $user->assignRole('supervisor');

        $this->assertSame(route('dispatch.control-room'), app(UserHomeRouteService::class)->resolve($user));
    }

    public function test_platform_admin_lands_on_tenant_management(): void
    {
        $user = User::create([
            'tenant_id' => null,
            'name' => 'Platform Admin',
            'email' => 'platform@test.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $user->assignRole('super-admin');
        $this->actingAs($user);

        $this->assertSame(route('saas.tenants'), app(UserHomeRouteService::class)->resolve($user));
    }

    public function test_root_route_redirects_authenticated_guard_to_field_app(): void
    {
        $tenant = Tenant::create(['name' => 'Root Co', 'slug' => 'root-co', 'status' => 'active']);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Guard',
            'email' => 'guard-root@test.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $user->assignRole('guard');

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('guard.mobile'));
    }
}
