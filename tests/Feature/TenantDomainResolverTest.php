<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TenantDomainResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TenantDomainResolverTest extends TestCase
{
    use RefreshDatabase;

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
}
