<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ScheduledCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_scheduled_artisan_commands_run_successfully(): void
    {
        Tenant::create(['name' => 'Sched Co', 'slug' => 'sched-co', 'status' => 'active']);

        $this->assertEquals(0, Artisan::call('guardops:analytics-snapshot'));
        $this->assertEquals(0, Artisan::call('guardops:compliance-expiry'));
        $this->assertEquals(0, Artisan::call('guardops:missed-patrols'));
    }
}
