<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use App\Services\TenantBrandingService;
use App\Services\TenantDomainResolver;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantDomainResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_uses_the_theme_of_its_tenant_domain(): void
    {
        config(['tenancy.base_domain' => 'guardops.test']);
        $tenant = Tenant::factory()->create([
            'subdomain' => 'acme',
            'domain' => 'security.example.test',
            'status' => 'active',
        ]);
        TenantSetting::create([
            'tenant_id' => $tenant->id,
            'key' => TenantBrandingService::THEME_KEY,
            'value' => ['light' => '#b91c1c', 'dark' => '#ef4444'],
        ]);

        foreach (['acme.guardops.test', 'security.example.test'] as $host) {
            $this->get('http://'.$host.'/login')
                ->assertOk()
                ->assertSee('--tenant-accent: #b91c1c;')
                ->assertSee('--tenant-dark-accent: #ef4444;');
        }

        $this->get('http://guardops.test/login')
            ->assertOk()
            ->assertDontSee('--tenant-accent: #b91c1c;');

        $this->get('http://guardops.test/login?organization='.$tenant->slug)
            ->assertOk()
            ->assertSee('--tenant-accent: #b91c1c;');
    }

    public function test_resolves_tenant_from_subdomain(): void
    {
        Tenant::create([
            'name' => 'Acme Security',
            'slug' => 'acme',
            'subdomain' => 'acme',
            'status' => 'active',
        ]);

        config(['tenancy.base_domain' => 'guardops.test']);

        $request = Request::create('http://acme.guardops.test/dashboard');
        $tenant = app(TenantDomainResolver::class)->resolveFromRequest($request);

        $this->assertNotNull($tenant);
        $this->assertEquals('acme', $tenant->slug);
    }

    public function test_shared_login_remembers_the_organization_palette_after_sign_out(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        TenantSetting::create([
            'tenant_id' => $tenant->id,
            'key' => TenantBrandingService::THEME_KEY,
            'value' => ['light' => '#b91c1c', 'dark' => '#ef4444'],
        ]);

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('--tenant-accent: #b91c1c;');

        $tenant->update(['status' => 'suspended']);

        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('--tenant-accent: #b91c1c;');
    }
}
