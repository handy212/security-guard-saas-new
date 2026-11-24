<?php

namespace Tests\Feature;

use App\Events\OperationalAlertsChanged;
use App\Jobs\DeliverOperationalBroadcast;
use App\Livewire\Notifications\NotificationBell;
use App\Livewire\Notifications\NotificationCenter;
use App\Models\AuditLog;
use App\Models\OperationalBroadcastDelivery;
use App\Models\Tenant;
use App\Models\User;
use App\Services\NotificationDispatcher;
use App\Services\TenantRoleProvisioner;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Broadcasting\BroadcastEvent;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Contracts\Broadcasting\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class OperationalAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_alarms_share_an_audited_acknowledgment_without_resolution(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $tenant = Tenant::create(['name' => 'Operations', 'slug' => 'operations', 'status' => 'active']);
        $operator = User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'active']);
        app(TenantRoleProvisioner::class)->assignRole($operator, 'company-admin');
        $dispatcher = app(NotificationDispatcher::class);

        $alert = $dispatcher->publishAlarm($tenant->id, 'sos.raised', '1', 'Guard SOS', 'Review in Dispatch', '/dispatch');
        $duplicate = $dispatcher->publishAlarm($tenant->id, 'sos.raised', '1', 'Guard SOS', 'Review in Dispatch', '/dispatch');
        $this->assertSame($alert->id, $duplicate->id);
        $this->assertSame(1, $dispatcher->alarmsFor($operator)->count());

        $dispatcher->acknowledgeAlarm($operator, $alert->id);
        $dispatcher->acknowledgeAlarm($operator, $alert->id);
        $this->assertSame($operator->id, $alert->fresh()->acknowledged_by);
        $this->assertNull($alert->fresh()->resolved_at);
        $this->assertSame(1, AuditLog::where('action', 'operational_alert.acknowledged')->count());

        $outsider = User::factory()->create(['status' => 'active']);
        $this->assertSame(0, $dispatcher->alarmsFor($outsider)->count());
    }

    public function test_operator_can_reload_and_acknowledge_multiple_alarms_without_marking_inbox_read(): void
    {
        $this->seed();
        $operator = User::where('email', 'admin@demo.test')->firstOrFail();
        $dispatcher = app(NotificationDispatcher::class);
        $first = $dispatcher->publishAlarm($operator->tenant_id, 'sos.raised', '1', 'Guard SOS', 'Review in Dispatch', '/dispatch');
        $dispatcher->publishAlarm($operator->tenant_id, 'patriot.intake', '2', 'Patriot intake', 'Review in Dispatch', '/dispatch');
        $operator->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => 'test',
            'data' => ['title' => 'Routine update'],
        ]);

        Livewire::actingAs($operator)->test(NotificationBell::class)
            ->assertSeeInOrder(['Guard SOS', 'Patriot intake'])
            ->call('acknowledge', $first->id)
            ->assertDontSee('Guard SOS')
            ->assertSee('Patriot intake');
        $this->assertSame(1, $operator->unreadNotifications()->count());

        Livewire::test(NotificationBell::class)->assertSee('Patriot intake');
        $this->get(route('notifications.index'))->assertOk()->assertSee('Guard SOS')->assertSee('Acknowledged');
        $this->assertNull($first->fresh()->resolved_at);
    }

    public function test_other_tenant_cannot_acknowledge_a_restricted_alarm(): void
    {
        $this->seed();
        $operator = User::where('email', 'admin@demo.test')->firstOrFail();
        $otherTenant = Tenant::create(['name' => 'Other', 'slug' => 'other', 'status' => 'active']);
        $alert = app(NotificationDispatcher::class)->publishAlarm($otherTenant->id, 'sos.raised', '1', 'Restricted SOS', 'Restricted', '/dispatch');
        setPermissionsTeamId($operator->tenant_id);

        Livewire::actingAs($operator)->test(NotificationBell::class)
            ->assertDontSee('Restricted SOS')
            ->call('acknowledge', $alert->id)
            ->assertStatus(404);
        $this->assertNull($alert->fresh()->acknowledged_at);
    }

    public function test_policy_deadline_escalates_once_and_acknowledgment_cancels_escalation(): void
    {
        $this->seed();
        $operator = User::where('email', 'admin@demo.test')->firstOrFail();
        $dispatcher = app(NotificationDispatcher::class);
        Livewire::actingAs($operator)->test(NotificationCenter::class)
            ->set('policyEvent', 'sos.raised')
            ->set('policy.acknowledgment_seconds', 20)
            ->call('savePolicy')
            ->assertHasNoErrors();
        $due = $dispatcher->publishAlarm($operator->tenant_id, 'sos.raised', '1', 'Guard SOS', 'Review', '/dispatch');
        $handled = $dispatcher->publishAlarm($operator->tenant_id, 'sos.raised', '2', 'Guard SOS', 'Review', '/dispatch');
        $dispatcher->acknowledgeAlarm($operator, $handled->id);
        $dispatcher->escalateDueAlarms();
        $this->assertNull($due->fresh()->escalated_at);

        $this->travel(21)->seconds();
        $dispatcher->escalateDueAlarms();
        $dispatcher->escalateDueAlarms();

        $this->assertNotNull($due->fresh()->escalated_at);
        $this->assertNull($handled->fresh()->escalated_at);
        $this->assertSame(1, $operator->unreadNotifications()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'operational_alert.policy_updated']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'operational_alert.escalated', 'auditable_id' => $due->id]);

        $dispatcher->resolveAlarm($operator->tenant_id, 'sos.raised', '2', $operator->id);
        $this->assertNotNull($handled->fresh()->resolved_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'operational_alert.resolved', 'auditable_id' => $handled->id]);
    }

    public function test_an_unroutable_escalation_remains_pending_and_retries_when_a_recipient_is_available(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $tenant = Tenant::create(['name' => 'Operations', 'slug' => 'operations', 'status' => 'active']);
        $dispatcher = app(NotificationDispatcher::class);
        $alert = $dispatcher->publishAlarm($tenant->id, 'sos.raised', '1', 'Guard SOS', 'Review', '/dispatch');

        $this->travel(3)->minutes();
        $dispatcher->escalateDueAlarms();
        $dispatcher->escalateDueAlarms();
        $this->assertNotNull($alert->fresh()->escalation_failed_at);
        $this->assertNull($alert->fresh()->escalated_at);
        $this->assertSame(1, AuditLog::where('action', 'operational_alert.escalation_failed')->count());

        $operator = User::factory()->create(['tenant_id' => $tenant->id, 'status' => 'active']);
        app(TenantRoleProvisioner::class)->assignRole($operator, 'company-admin');
        $dispatcher->escalateDueAlarms();
        $this->assertNotNull($alert->fresh()->escalated_at);
        $this->assertNull($alert->fresh()->escalation_failed_at);
        $this->assertSame(1, $operator->unreadNotifications()->count());
    }

    public function test_archiving_a_routine_notification_preserves_alarm_attention(): void
    {
        $this->seed();
        $operator = User::where('email', 'admin@demo.test')->firstOrFail();
        $alarm = app(NotificationDispatcher::class)->publishAlarm($operator->tenant_id, 'sos.raised', '1', 'Guard SOS', 'Review', '/dispatch');
        $notification = $operator->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => 'test', 'data' => ['title' => 'Routine update'],
        ]);

        Livewire::actingAs($operator)->test(NotificationCenter::class)
            ->call('archive', $notification->id)
            ->assertDontSee('Routine update')
            ->set('filter', 'archived')
            ->assertSee('Routine update')
            ->call('restore', $notification->id)
            ->assertDontSee('Routine update');
        Livewire::test(NotificationBell::class)->assertSee('Guard SOS');
        $this->assertNull($alarm->fresh()->acknowledged_at);
    }

    public function test_patriot_connectivity_notifies_only_when_health_changes(): void
    {
        $this->seed();
        $operator = User::where('email', 'admin@demo.test')->firstOrFail();
        $dispatcher = app(NotificationDispatcher::class);

        $dispatcher->observePatriotConnectivity($operator->tenant_id, true);
        $dispatcher->observePatriotConnectivity($operator->tenant_id, false);
        $dispatcher->observePatriotConnectivity($operator->tenant_id, false);
        $dispatcher->observePatriotConnectivity($operator->tenant_id, true);
        $dispatcher->observePatriotConnectivity($operator->tenant_id, true);

        $this->assertSame(1, $operator->notifications()->where('data->event_type', 'patriot.connection_unavailable')->count());
        $this->assertSame(1, $operator->notifications()->where('data->event_type', 'patriot.connection_restored')->count());
    }

    public function test_backup_reminder_is_delivered_once_even_when_the_first_stage_has_no_recipient(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        $dispatcher = app(NotificationDispatcher::class);
        Livewire::actingAs($admin)->test(NotificationCenter::class)
            ->set('policyEvent', 'sos.raised')
            ->set('policy.acknowledgment_seconds', 20)
            ->set('policy.backup_delay_seconds', 30)
            ->set('policy.backup_permission', 'dispatch.manage')
            ->call('savePolicy')->assertHasNoErrors();

        $alert = $dispatcher->publishAlarm($admin->tenant_id, 'sos.raised', '101', 'Guard SOS', 'Review', '/dispatch');
        $handled = $dispatcher->publishAlarm($admin->tenant_id, 'sos.raised', '102', 'Guard SOS', 'Review', '/dispatch');
        $dispatcher->acknowledgeAlarm($admin, $handled->id);
        $admin->removeRole('company-admin');
        $admin->assignRole('supervisor');

        $this->travel(21)->seconds();
        $dispatcher->escalateDueAlarms();
        $this->assertNotNull($alert->fresh()->escalation_failed_at);
        $this->assertSame(0, $admin->notifications()->count());

        $this->travel(30)->seconds();
        $dispatcher->escalateDueAlarms();
        $dispatcher->escalateDueAlarms();
        $this->assertNotNull($alert->fresh()->backup_escalated_at);
        $this->assertNull($handled->fresh()->backup_escalated_at);
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertDatabaseHas('audit_logs', ['action' => 'operational_alert.backup_escalated', 'auditable_id' => $alert->id]);
    }

    public function test_exhausted_broadcast_jobs_are_counted_only_for_the_current_tenant(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        $otherTenant = Tenant::create(['name' => 'Other', 'slug' => 'other', 'status' => 'active']);

        foreach ([$admin->tenant_id, $otherTenant->id] as $tenantId) {
            $event = new OperationalAlertsChanged($tenantId);
            $job = new BroadcastEvent($event);
            $job->failed(new \RuntimeException('Provider details'));
        }

        setPermissionsTeamId($admin->tenant_id);
        Livewire::actingAs($admin)->test(NotificationCenter::class)
            ->assertViewHas('broadcastFailures', 1)
            ->assertDontSee('Provider details');
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id' => $admin->tenant_id, 'action' => 'operational_alert.broadcast_failed',
        ]);
    }

    public function test_broadcast_attempts_are_recorded_until_handoff_succeeds(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        Bus::fake([DeliverOperationalBroadcast::class]);

        $alarm = app(NotificationDispatcher::class)->publishAlarm($admin->tenant_id, 'sos.raised', '555', 'Guard SOS', 'Review', '/dispatch');
        $delivery = OperationalBroadcastDelivery::forTenant($admin->tenant_id)->sole();
        Bus::assertDispatched(DeliverOperationalBroadcast::class, fn ($job) => $job->deliveryId === $delivery->id);
        $this->assertNull($delivery->sent_at);

        $job = new DeliverOperationalBroadcast($admin->tenant_id, $delivery->id);
        $factory = \Mockery::mock(Factory::class);
        $broadcaster = \Mockery::mock(Broadcaster::class);
        $factory->shouldReceive('connection')->with($delivery->connection)->twice()->andReturn($broadcaster);
        $channel = 'private-tenant.'.$admin->tenant_id.'.dispatch';
        $broadcaster->shouldReceive('broadcast')->with([$channel], 'operational-alerts.changed', [])->once()->ordered()
            ->andThrow(new \RuntimeException('Provider details'));
        $broadcaster->shouldReceive('broadcast')->with([$channel], 'operational-alerts.changed', [])->once()->ordered();

        try {
            $job->handle($factory);
            $this->fail('The worker must retry failed sends.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Provider details', $exception->getMessage());
        }

        $this->assertNull($delivery->fresh()->sent_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'operational_alert.broadcast_attempt_failed', 'auditable_id' => $delivery->id]);
        $job->handle($factory);
        $job->handle($factory);

        $this->assertSame(2, $delivery->fresh()->attempts);
        $this->assertNotNull($delivery->fresh()->sent_at);
        $this->assertNull($alarm->fresh()->acknowledged_at);

        Livewire::actingAs($admin)->test(NotificationCenter::class)
            ->assertViewHas('deliveryMetrics', fn ($metrics) => $metrics->sent === 1 && $metrics->attempts === 2)
            ->assertDontSee('Provider details');
    }
}
