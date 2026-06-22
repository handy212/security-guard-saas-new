<?php

namespace Tests\Feature;

use App\Models\ClientAccount;
use App\Models\Guard;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_scope_limits_client_records(): void
    {
        $tenantA = Tenant::create(['name' => 'A Security', 'slug' => 'a-security', 'status' => 'active']);
        $tenantB = Tenant::create(['name' => 'B Security', 'slug' => 'b-security', 'status' => 'active']);

        ClientAccount::create(['tenant_id' => $tenantA->id, 'name' => 'Client A', 'status' => 'active']);
        ClientAccount::create(['tenant_id' => $tenantB->id, 'name' => 'Client B', 'status' => 'active']);

        app()->instance('currentTenant', $tenantA);

        $this->assertEquals(1, ClientAccount::count());
        $this->assertEquals('Client A', ClientAccount::first()->name);
    }
}
