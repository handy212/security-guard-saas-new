<?php

namespace Tests\Feature;

use App\Models\Guard;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiTenantScopedValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_cannot_check_in_visitor_at_another_tenants_site(): void
    {
        $this->seed();

        $tenantA = Tenant::first();
        $tenantB = Tenant::create(['name' => 'B Security', 'slug' => 'b-security', 'status' => 'active']);

        $otherSite = Site::create([
            'tenant_id' => $tenantB->id,
            'client_account_id' => \App\Models\ClientAccount::create([
                'tenant_id' => $tenantB->id,
                'name' => 'B Client',
                'status' => 'active',
            ])->id,
            'name' => 'Foreign Site',
            'status' => 'active',
        ]);

        Sanctum::actingAs(User::where('email', 'john.guard@test')->first());

        $this->postJson('/api/v1/visitors/check-in', [
            'site_id' => $otherSite->id,
            'visitor_name' => 'Jane Doe',
        ])->assertUnprocessable();
    }

    public function test_guard_can_check_in_visitor_at_own_tenant_site(): void
    {
        $this->seed();

        $site = Site::first();
        Sanctum::actingAs(User::where('email', 'john.guard@test')->first());

        $this->postJson('/api/v1/visitors/check-in', [
            'site_id' => $site->id,
            'visitor_name' => 'Jane Doe',
        ])->assertCreated();
    }

    public function test_guard_cannot_report_incident_for_another_tenants_site(): void
    {
        $this->seed();

        $tenantB = Tenant::create(['name' => 'B Security', 'slug' => 'b-security-2', 'status' => 'active']);
        $otherSite = Site::create([
            'tenant_id' => $tenantB->id,
            'client_account_id' => \App\Models\ClientAccount::create([
                'tenant_id' => $tenantB->id,
                'name' => 'B Client',
                'status' => 'active',
            ])->id,
            'name' => 'Foreign Site',
            'status' => 'active',
        ]);

        Sanctum::actingAs(User::where('email', 'john.guard@test')->first());

        $this->postJson('/api/v1/incidents', [
            'site_id' => $otherSite->id,
            'title' => 'Test',
            'type' => 'theft',
            'severity' => 'medium',
            'description' => 'Test incident',
        ])->assertUnprocessable();
    }
}
