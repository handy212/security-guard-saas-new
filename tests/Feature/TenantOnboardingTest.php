<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Services\TenantOnboardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_progress_starts_at_zero(): void
    {
        $tenant = Tenant::create(['name' => 'New Co', 'slug' => 'new-co', 'status' => 'active']);

        $service = app(TenantOnboardingService::class);

        $this->assertEquals(0, $service->progress($tenant->id));
        $this->assertFalse($service->isComplete($tenant->id));
        $this->assertCount(5, $service->steps($tenant->id));
    }
}
