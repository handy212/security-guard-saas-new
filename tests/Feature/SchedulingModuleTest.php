<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulingModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@demo.test')->first();
        app()->instance('currentTenant', $this->admin->tenant);
    }

    public function test_schedule_hub_pages_load(): void
    {
        $routes = [
            '/schedules',
            '/schedules/templates',
            '/schedules/attendance',
            '/schedules/shift-status',
            '/schedules/open-shifts',
            '/schedules/shift-exchange',
            '/schedules/time-off',
            '/schedules/calendar',
            '/schedules/deployment-sheet',
        ];

        foreach ($routes as $route) {
            $this->actingAs($this->admin)
                ->get($route)
                ->assertOk();
        }
    }

    public function test_legacy_schedule_routes_redirect(): void
    {
        $this->actingAs($this->admin)
            ->get('/workforce')
            ->assertRedirect('/schedules/time-off');

        $this->actingAs($this->admin)
            ->get('/attendance/timekeeping')
            ->assertRedirect('/schedules/attendance');

        $this->actingAs($this->admin)
            ->get('/schedules/marketplace')
            ->assertRedirect('/schedules/open-shifts');
    }
}
