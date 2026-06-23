<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DispatchService;
use App\Services\IncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_incident_submission_queues_admin_notification(): void
    {
        Notification::fake();

        $tenant = Tenant::create(['name' => 'Notify Co', 'slug' => 'notify-co', 'status' => 'active']);
        app()->instance('currentTenant', $tenant);

        $admin = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin',
            'email' => 'admin@notify.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        $admin->assignRole('company-admin');
        $this->actingAs($admin);

        $site = Site::create([
            'tenant_id' => $tenant->id,
            'client_account_id' => \App\Models\ClientAccount::create([
                'tenant_id' => $tenant->id,
                'name' => 'Client',
                'status' => 'active',
            ])->id,
            'name' => 'Site A',
            'status' => 'active',
        ]);

        app(IncidentService::class)->submit([
            'tenant_id' => $tenant->id,
            'site_id' => $site->id,
            'title' => 'Perimeter breach',
            'incident_type' => 'security',
            'type' => 'security',
            'severity' => 'high',
            'description' => 'Fence damaged',
            'reported_by_user_id' => $admin->id,
        ]);

        Notification::assertSentTo($admin, \App\Notifications\IncidentSubmittedNotification::class);
        $this->assertDatabaseHas('audit_logs', ['action' => 'incident.submitted']);
    }

    public function test_sos_raises_audit_log_entry(): void
    {
        $tenant = Tenant::create(['name' => 'SOS Co', 'slug' => 'sos-co', 'status' => 'active']);
        app()->instance('currentTenant', $tenant);
        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Guard User',
            'email' => 'guard@sos.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        \App\Models\Guard::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'employee_number' => 'G-SOS',
            'first_name' => 'SOS',
            'last_name' => 'Guard',
            'status' => 'active',
        ]);
        $user = $user->fresh('guardProfile');

        $this->actingAs($user);

        app(DispatchService::class)->raiseSos($user, [
            'latitude' => 1.1,
            'longitude' => 2.2,
            'message' => 'Help',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'sos.raised']);
        $this->assertDatabaseHas('sos_alerts', ['status' => 'open']);
    }
}
