<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlanEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlanEntitlementTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_plan_blocks_premium_routes(): void
    {
        $this->seed();

        $tenant = Tenant::first();
        $starter = SubscriptionPlan::where('slug', 'starter')->first();

        $tenant->subscription()->update(['subscription_plan_id' => $starter->id]);
        $tenant->update(['plan_id' => $starter->id]);

        $admin = User::where('email', 'admin@demo.test')->first();

        $this->actingAs($admin)
            ->get(route('dispatch.control-room'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('analytics.dashboard'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('guards.index'))
            ->assertOk();
    }

    public function test_enterprise_plan_includes_all_modules(): void
    {
        $this->seed();

        $tenant = Tenant::first();
        $entitlements = app(PlanEntitlementService::class);

        $this->assertTrue($entitlements->tenantHasFeature($tenant->id, 'dispatch'));
        $this->assertTrue($entitlements->tenantHasFeature($tenant->id, 'analytics'));
        $this->assertTrue($entitlements->tenantHasFeature($tenant->id, 'billing'));
    }

    public function test_starter_and_enterprise_have_different_entitlements(): void
    {
        $this->seed();

        $starter = SubscriptionPlan::where('slug', 'starter')->first();
        $enterprise = SubscriptionPlan::where('slug', 'enterprise')->first();

        $this->assertNotEquals($starter->features, $enterprise->features);
        $this->assertContains('dispatch', $enterprise->features);
        $this->assertNotContains('dispatch', $starter->features);
    }

    public function test_company_admin_can_reset_team_password(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->first();
        $guardUser = User::where('email', 'john.guard@test')->first();

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Settings\TeamPasswordReset::class)
            ->call('selectUser', $guardUser->id)
            ->set('newPassword', 'NewSecurePass1!')
            ->call('resetPassword')
            ->assertHasNoErrors();

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('NewSecurePass1!', $guardUser->fresh()->password)
        );
    }
}
