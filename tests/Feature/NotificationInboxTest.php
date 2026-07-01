<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\IncidentSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_incident_notification_is_stored_in_database(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->first();
        $tenant = Tenant::first();
        app()->instance('currentTenant', $tenant);

        $site = Site::first();
        $incident = Incident::create([
            'tenant_id' => $tenant->id,
            'site_id' => $site->id,
            'title' => 'Test breach',
            'incident_type' => 'security',
            'severity' => 'high',
            'description' => 'Fence damaged',
            'status' => 'submitted',
            'reported_at' => now(),
            'occurred_at' => now(),
        ]);

        $admin->notify(new IncidentSubmittedNotification($incident));

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'notifiable_type' => User::class,
        ]);

        $this->assertEquals(1, $admin->unreadNotifications()->count());
    }

    public function test_notification_bell_renders_for_tenant_admin(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->first();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeLivewire(\App\Livewire\Notifications\NotificationBell::class);
    }
}
