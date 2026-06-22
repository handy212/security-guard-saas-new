<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_cannot_access_role_settings(): void
    {
        $this->seed();

        $guardUser = User::where('email', 'john.guard@test')->first();

        $this->actingAs($guardUser)
            ->get('/settings/roles')
            ->assertForbidden();
    }

    public function test_company_admin_can_view_dashboard_with_tenant(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->first();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk();
    }
}
