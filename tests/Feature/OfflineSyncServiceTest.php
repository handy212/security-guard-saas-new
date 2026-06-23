<?php

namespace Tests\Feature;

use App\Models\Guard;
use App\Models\PatrolCheckpoint;
use App\Models\PatrolRoute;
use App\Models\PatrolSession;
use App\Models\ShiftAssignment;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OfflineSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_processes_checkpoint_scan_from_offline_batch(): void
    {
        $tenant = Tenant::create(['name' => 'Offline Co', 'slug' => 'offline-co', 'status' => 'active']);
        app()->instance('currentTenant', $tenant);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $guard = Guard::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'employee_number' => 'G-10',
            'first_name' => 'Off',
            'last_name' => 'Line',
            'status' => 'active',
        ]);

        $client = \App\Models\ClientAccount::create(['tenant_id' => $tenant->id, 'name' => 'Client', 'status' => 'active']);
        $site = Site::create(['tenant_id' => $tenant->id, 'client_account_id' => $client->id, 'name' => 'Site', 'status' => 'active', 'latitude' => 6.2, 'longitude' => -1.6]);
        $route = PatrolRoute::create(['tenant_id' => $tenant->id, 'site_id' => $site->id, 'name' => 'Round', 'status' => 'active']);
        $shift = \App\Models\Shift::create([
            'tenant_id' => $tenant->id,
            'client_account_id' => $client->id,
            'site_id' => $site->id,
            'title' => 'Shift',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(8),
            'required_guards' => 1,
            'status' => 'open',
        ]);
        $checkpoint = PatrolCheckpoint::create([
            'tenant_id' => $tenant->id,
            'patrol_route_id' => $route->id,
            'name' => 'Gate',
            'code' => 'GATE-QR-001',
            'sequence' => 1,
            'status' => 'active',
        ]);
        $assignment = ShiftAssignment::create([
            'tenant_id' => $tenant->id,
            'shift_id' => $shift->id,
            'guard_id' => $guard->id,
            'status' => 'assigned',
        ]);
        $session = PatrolSession::create([
            'tenant_id' => $tenant->id,
            'patrol_route_id' => $route->id,
            'guard_id' => $guard->id,
            'shift_assignment_id' => $assignment->id,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        $service = app(OfflineSyncService::class);
        $batch = $service->queue([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'payload' => [[
                'type' => 'checkpoint_scan',
                'patrol_session_id' => $session->id,
                'checkpoint_code' => $checkpoint->code,
                'latitude' => 6.2,
                'longitude' => -1.6,
            ]],
        ]);

        $service->process($batch);

        $this->assertDatabaseHas('checkpoint_scans', [
            'patrol_session_id' => $session->id,
            'patrol_checkpoint_id' => $checkpoint->id,
        ]);
        $this->assertEquals('processed', $batch->fresh()->status);
    }

    public function test_processes_clock_in_from_offline_batch(): void
    {
        $tenant = Tenant::create(['name' => 'Clock Co', 'slug' => 'clock-co', 'status' => 'active']);
        app()->instance('currentTenant', $tenant);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $guard = Guard::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'employee_number' => 'G-11',
            'first_name' => 'Clock',
            'last_name' => 'Guard',
            'status' => 'active',
        ]);
        $client = \App\Models\ClientAccount::create(['tenant_id' => $tenant->id, 'name' => 'Client', 'status' => 'active']);
        $site = Site::create(['tenant_id' => $tenant->id, 'client_account_id' => $client->id, 'name' => 'Site', 'status' => 'active', 'latitude' => 6.2, 'longitude' => -1.6]);
        $shift = \App\Models\Shift::create([
            'tenant_id' => $tenant->id,
            'client_account_id' => $client->id,
            'site_id' => $site->id,
            'title' => 'Shift',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(8),
            'required_guards' => 1,
            'status' => 'open',
        ]);
        $assignment = ShiftAssignment::create([
            'tenant_id' => $tenant->id,
            'shift_id' => $shift->id,
            'guard_id' => $guard->id,
            'status' => 'assigned',
        ]);

        $service = app(OfflineSyncService::class);
        $batch = $service->queue([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'payload' => [[
                'type' => 'clock_in',
                'shift_assignment_id' => $assignment->id,
                'latitude' => 6.2,
                'longitude' => -1.6,
                'enforce_geofence' => false,
            ]],
        ]);

        $service->process($batch);

        $this->assertEquals('processed', $batch->fresh()->status, json_encode($batch->fresh()->result));
        $this->assertDatabaseHas('attendance_logs', [
            'shift_assignment_id' => $assignment->id,
            'guard_id' => $guard->id,
        ]);
    }
}
