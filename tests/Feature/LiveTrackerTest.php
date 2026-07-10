<?php

namespace Tests\Feature;

use App\Livewire\Tracking\LiveTracker;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LiveTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_tracker_page_renders(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        app()->instance('currentTenant', $admin->tenant);

        $this->actingAs($admin)
            ->get(route('tracking.live'))
            ->assertOk()
            ->assertSee('Live Tracker')
            ->assertSee('Open dispatch');
    }

    public function test_live_tracker_can_filter_by_site(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        $tenant = Tenant::findOrFail($admin->tenant_id);
        app()->instance('currentTenant', $tenant);
        setPermissionsTeamId($tenant->id);

        $site = Site::where('tenant_id', $tenant->id)->firstOrFail();

        Livewire::actingAs($admin)
            ->test(LiveTracker::class)
            ->set('siteFilter', $site->id)
            ->assertSet('siteFilter', $site->id)
            ->assertSee('Live guards');
    }
}
