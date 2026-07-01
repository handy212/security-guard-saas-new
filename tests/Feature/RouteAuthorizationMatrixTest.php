<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteAuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    /** @dataProvider protectedRoutesProvider */
    public function test_guard_cannot_access_protected_operations_routes(string $route): void
    {
        $this->seed();
        app()->instance('currentTenant', Tenant::first());

        $this->actingAs(User::where('email', 'john.guard@test')->first())
            ->get($route)
            ->assertForbidden();
    }

    public static function protectedRoutesProvider(): array
    {
        return [
            'analytics' => ['/analytics'],
            'billing invoices' => ['/billing/invoices'],
            'billing payroll' => ['/billing/payroll'],
            'settings' => ['/settings'],
            'schedules marketplace' => ['/schedules/marketplace'],
            'compliance policies' => ['/compliance/policies'],
        ];
    }

    public function test_guard_can_access_field_app(): void
    {
        $this->seed();
        app()->instance('currentTenant', Tenant::first());

        $this->actingAs(User::where('email', 'john.guard@test')->first())
            ->get('/guard')
            ->assertOk();
    }
}
