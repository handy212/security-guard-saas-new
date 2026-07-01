<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformAdminTest extends TestCase
{
    use RefreshDatabase;

    private function platformAdmin(): User
    {
        return User::where('email', 'platform@guardops.test')->first();
    }

    public function test_platform_admin_login_redirects_to_tenants(): void
    {
        $this->seed();

        $this->post(route('login'), [
            'email' => 'platform@guardops.test',
            'password' => 'password',
        ])->assertRedirect(route('saas.tenants'));
    }

    public function test_saas_root_redirects_to_tenants(): void
    {
        $this->seed();

        $this->actingAs($this->platformAdmin())
            ->get('/saas')
            ->assertRedirect('/saas/tenants');
    }

    public function test_super_admin_can_access_tenant_management(): void
    {
        $this->seed();

        $this->actingAs($this->platformAdmin())
            ->get(route('saas.tenants'))
            ->assertOk()
            ->assertSee('Tenants')
            ->assertSee('Add tenant')
            ->assertSee('Demo Security Company')
            ->assertDontSee('Dispatch');
    }

    public function test_company_admin_cannot_access_platform_routes(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->first();

        $this->actingAs($admin)->get(route('saas.tenants'))->assertForbidden();
        $this->actingAs($admin)->get(route('saas.plans'))->assertForbidden();
        $this->actingAs($admin)->post(route('saas.exit-tenant'))->assertForbidden();
    }

    public function test_super_admin_can_create_tenant_with_admin_user(): void
    {
        $this->seed();

        $plan = SubscriptionPlan::first();

        Livewire::actingAs($this->platformAdmin())
            ->test(\App\Livewire\Tenants\TenantManagement::class)
            ->set('tenantForm.name', 'New Security Co')
            ->set('tenantForm.slug', 'new-security')
            ->set('tenantForm.plan_id', (string) $plan->id)
            ->set('tenantForm.admin_name', 'Acme Admin')
            ->set('tenantForm.admin_email', 'admin@acme.test')
            ->set('tenantForm.admin_password', 'SecurePass123!')
            ->call('saveTenant')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tenants', ['slug' => 'new-security']);
        $this->assertDatabaseHas('users', ['email' => 'admin@acme.test']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'platform.tenant.created']);
    }

    public function test_stat_filter_and_clear_filters_work(): void
    {
        $this->seed();

        Livewire::actingAs($this->platformAdmin())
            ->test(\App\Livewire\Tenants\TenantManagement::class)
            ->call('applyStatFilter', 'without_plan')
            ->assertSet('planFilter', 'none')
            ->call('clearFilters')
            ->assertSet('statusFilter', 'all')
            ->assertSet('planFilter', 'all')
            ->assertSet('search', '');
    }

    public function test_suspend_tenant_writes_audit_log(): void
    {
        $this->seed();

        $tenant = Tenant::first();

        Livewire::actingAs($this->platformAdmin())
            ->test(\App\Livewire\Tenants\TenantManagement::class)
            ->call('updateTenantStatus', $tenant->id, 'suspended');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'platform.tenant.suspended',
            'auditable_id' => $tenant->id,
        ]);
    }

    public function test_super_admin_can_enter_and_exit_tenant_context(): void
    {
        $this->seed();

        $tenant = Tenant::first();

        Livewire::actingAs($this->platformAdmin())
            ->test(\App\Livewire\Tenants\TenantManagement::class)
            ->call('enterTenant', $tenant->id)
            ->assertRedirect(route('dashboard'));

        $this->assertEquals($tenant->slug, TenantContext::switchedTenantSlug());

        $this->actingAs($this->platformAdmin())
            ->get(route('dashboard'))
            ->assertOk();

        $this->actingAs($this->platformAdmin())
            ->post(route('saas.exit-tenant'))
            ->assertRedirect(route('saas.tenants'));

        $this->assertNull(TenantContext::switchedTenantSlug());
    }

    public function test_super_admin_can_manage_plans_and_subscriptions(): void
    {
        $this->seed();

        $this->actingAs($this->platformAdmin())
            ->get(route('saas.plans'))
            ->assertOk()
            ->assertSee('Plans');

        Livewire::actingAs($this->platformAdmin())
            ->test(\App\Livewire\Tenants\PlatformPlanManagement::class)
            ->set('form.name', 'Pro')
            ->set('form.slug', 'pro')
            ->set('form.monthly_price', 199)
            ->set('form.annual_price', 1990)
            ->set('form.selectedFeatures', ['guards', 'schedules', 'billing', 'dispatch'])
            ->call('save')
            ->assertHasNoErrors();

        $subscription = TenantSubscription::first();
        $plan = SubscriptionPlan::where('slug', 'starter')->first();

        Livewire::actingAs($this->platformAdmin())
            ->test(\App\Livewire\Tenants\PlatformSubscriptionManagement::class)
            ->call('openEdit', $subscription->id)
            ->set('form.subscription_plan_id', (string) $plan->id)
            ->set('form.status', 'active')
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_dashboard_redirects_platform_admin_to_tenants(): void
    {
        $this->seed();

        $this->actingAs($this->platformAdmin())
            ->get(route('dashboard'))
            ->assertRedirect(route('saas.tenants'));
    }

    public function test_platform_admin_can_reset_tenant_admin_password(): void
    {
        $this->seed();

        $tenant = Tenant::first();
        $admin = User::where('email', 'admin@demo.test')->first();

        Livewire::actingAs($this->platformAdmin())
            ->test(\App\Livewire\Tenants\TenantManagement::class)
            ->call('openViewTenant', $tenant->id)
            ->call('startResetPassword', $admin->id)
            ->set('resetPassword', 'ResetSecurePass1!')
            ->call('resetAdminPassword', $tenant->id)
            ->assertHasNoErrors();

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('ResetSecurePass1!', $admin->fresh()->password)
        );

        $this->assertDatabaseHas('audit_logs', ['action' => 'platform.tenant.password_reset']);
    }
}
