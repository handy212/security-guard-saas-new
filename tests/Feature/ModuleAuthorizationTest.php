<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\NavigationBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_user_cannot_access_dispatch_control_room(): void
    {
        $this->seed();

        app()->instance('currentTenant', Tenant::first());

        $this->actingAs(User::where('email', 'john.guard@test')->first())
            ->get('/dispatch')
            ->assertForbidden();
    }

    public function test_guard_user_cannot_access_client_management(): void
    {
        $this->seed();

        app()->instance('currentTenant', Tenant::first());

        $this->actingAs(User::where('email', 'john.guard@test')->first())
            ->get('/clients')
            ->assertForbidden();
    }

    public function test_company_admin_can_access_dispatch_control_room(): void
    {
        $this->seed();

        app()->instance('currentTenant', Tenant::first());

        $this->actingAs(User::where('email', 'admin@demo.test')->first())
            ->get('/dispatch')
            ->assertOk();
    }

    public function test_admin_navigation_hides_field_app(): void
    {
        $this->seed();
        app()->instance('currentTenant', Tenant::first());

        $this->actingAs(User::where('email', 'admin@demo.test')->first());

        $pinned = app(NavigationBuilder::class)->pinned();

        $this->assertFalse($pinned->contains(fn (array $link) => $link['href'] === '/guard'));
    }

    public function test_guard_navigation_includes_field_app(): void
    {
        $this->seed();
        app()->instance('currentTenant', Tenant::first());

        $this->actingAs(User::where('email', 'john.guard@test')->first());

        $pinned = app(NavigationBuilder::class)->pinned();

        $this->assertTrue($pinned->contains(fn (array $link) => $link['href'] === '/guard'));
    }
}
