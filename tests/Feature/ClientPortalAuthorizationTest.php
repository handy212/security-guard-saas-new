<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortalAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_operations_user_cannot_access_client_portal_routes(): void
    {
        $this->seed();

        app()->instance('currentTenant', Tenant::first());

        $this->actingAs(User::where('email', 'admin@demo.test')->first())
            ->get('/client-portal')
            ->assertForbidden();
    }
}
