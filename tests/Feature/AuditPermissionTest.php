<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_can_access_audit_log_after_seeding(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->first();
        app()->instance('currentTenant', Tenant::first());

        $this->actingAs($admin)
            ->get('/settings/audit-log')
            ->assertOk();
    }

    public function test_audit_view_permission_exists_in_role_seeder(): void
    {
        $this->seed();

        $this->assertDatabaseHas('permissions', ['name' => 'audit.view', 'guard_name' => 'web']);

        $admin = User::where('email', 'admin@demo.test')->first();
        $this->assertTrue($admin->can('audit.view'));
    }
}
