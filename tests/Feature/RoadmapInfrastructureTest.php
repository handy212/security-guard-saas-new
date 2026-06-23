<?php

namespace Tests\Feature;

use App\Models\Guard;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use App\Services\GuardLocationService;
use App\Services\PlanLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoadmapInfrastructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_limits_block_guard_creation_when_at_capacity(): void
    {
        $tenant = Tenant::create(['name' => 'Limit Co', 'slug' => 'limit-co', 'status' => 'active']);
        \App\Models\BillingLimit::create([
            'tenant_id' => $tenant->id,
            'max_guards' => 1,
            'max_sites' => 1,
            'max_clients' => 1,
            'storage_mb' => 100,
        ]);

        Guard::create([
            'tenant_id' => $tenant->id,
            'employee_number' => 'G-1',
            'first_name' => 'A',
            'last_name' => 'Guard',
            'status' => 'active',
        ]);

        $service = app(PlanLimitService::class);
        $this->assertFalse($service->canCreateGuard($tenant));
        $this->assertTrue($service->canCreateSite($tenant));
    }

    public function test_guard_location_service_records_coordinates(): void
    {
        $tenant = Tenant::create(['name' => 'GPS Co', 'slug' => 'gps-co', 'status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Guard::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'employee_number' => 'G-2',
            'first_name' => 'GPS',
            'last_name' => 'Guard',
            'status' => 'active',
        ]);
        $user = $user->fresh('guardProfile');

        $location = app(GuardLocationService::class)->record($user, 6.2, -1.6, 5.0);

        $this->assertDatabaseHas('guard_locations', [
            'id' => $location->id,
            'guard_id' => $user->guardProfile->id,
            'latitude' => 6.2,
        ]);
    }

    public function test_webhook_subscriptions_table_exists(): void
    {
        $tenant = Tenant::create(['name' => 'Hook Co', 'slug' => 'hook-co', 'status' => 'active']);

        \App\Models\WebhookSubscription::create([
            'tenant_id' => $tenant->id,
            'event' => 'incident.submitted',
            'target_url' => 'https://example.com/hook',
            'secret' => 'test-secret',
            'is_active' => true,
        ]);

        $this->assertDatabaseCount('webhook_subscriptions', 1);
    }
}
