<?php

namespace Tests\Feature;

use App\Enums\DispatchStatus;
use App\Models\ClientAccount;
use App\Models\DispatchEvent;
use App\Models\Guard;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DispatchSystemTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@demo.test')->first();
        $this->tenant = $this->admin->tenant;
        app()->instance('currentTenant', $this->tenant);
    }

    public function test_dispatch_board_is_accessible(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dispatch.control-room'))
            ->assertOk()
            ->assertSee('Dispatch Center')
            ->assertSee('New dispatch');
    }

    public function test_create_dispatch_with_full_workflow(): void
    {
        $client = ClientAccount::where('tenant_id', $this->tenant->id)->first();
        $site = Site::where('tenant_id', $this->tenant->id)->first();
        $guard = Guard::where('tenant_id', $this->tenant->id)->first();

        $service = app(DispatchService::class);

        $event = $service->createDispatch([
            'tenant_id' => $this->tenant->id,
            'client_account_id' => $client->id,
            'site_id' => $site->id,
            'guard_id' => $guard->id,
            'created_by_user_id' => $this->admin->id,
            'event_type' => 'alarm',
            'priority' => 'high',
            'caller_type' => 'client',
            'caller_name' => 'Jane Doe',
            'incident_location' => 'Loading dock',
            'incident_date' => now()->toDateString(),
            'incident_time' => '14:30',
            'description' => 'Alarm triggered at rear entrance.',
        ]);

        $this->assertDatabaseHas('dispatch_events', [
            'id' => $event->id,
            'dispatch_number' => $event->dispatch_number,
            'caller_name' => 'Jane Doe',
            'status' => DispatchStatus::ASSIGNED->value,
        ]);

        $this->assertDatabaseHas('dispatch_activity_logs', [
            'dispatch_event_id' => $event->id,
            'action' => 'created',
        ]);

        $service->advanceStatus($event->fresh(), $this->admin->id);
        $this->assertSame(DispatchStatus::EN_ROUTE, $event->fresh()->status);

        $service->advanceStatus($event->fresh(), $this->admin->id);
        $this->assertSame(DispatchStatus::ON_SCENE, $event->fresh()->status);
    }

    public function test_create_dispatch_via_livewire_form(): void
    {
        $client = ClientAccount::where('tenant_id', $this->tenant->id)->first();
        $site = Site::where('tenant_id', $this->tenant->id)->first();

        Livewire::actingAs($this->admin)
            ->test(\App\Livewire\Dispatch\DispatcherBoard::class)
            ->set('showForm', true)
            ->set('form.client_account_id', (string) $client->id)
            ->set('form.site_id', (string) $site->id)
            ->set('form.priority', 'high')
            ->set('form.caller_type', 'client')
            ->set('form.caller_name', 'Jane Doe')
            ->set('form.incident_location', 'Loading dock')
            ->set('form.event_type', 'alarm')
            ->set('form.incident_date', now()->toDateString())
            ->set('form.incident_time', '14:30')
            ->set('form.description', 'Alarm at rear entrance')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);
    }

    public function test_guard_can_advance_assigned_dispatch_via_api(): void
    {
        $guardUser = User::where('email', 'john.guard@test')->first();
        $guard = $guardUser->guardProfile;
        $client = ClientAccount::where('tenant_id', $this->tenant->id)->first();
        $site = Site::where('tenant_id', $this->tenant->id)->first();

        $event = DispatchEvent::create([
            'tenant_id' => $this->tenant->id,
            'dispatch_number' => 'DISP-2026-0001',
            'client_account_id' => $client->id,
            'site_id' => $site->id,
            'guard_id' => $guard->id,
            'event_type' => 'disturbance',
            'priority' => 'normal',
            'caller_type' => 'public',
            'caller_name' => 'Caller',
            'incident_location' => 'Lobby',
            'description' => 'Noise complaint',
            'status' => DispatchStatus::ASSIGNED,
            'opened_at' => now(),
            'assigned_at' => now(),
        ]);

        $this->actingAs($guardUser)
            ->postJson("/api/v1/dispatches/{$event->id}/advance")
            ->assertOk()
            ->assertJsonPath('status', DispatchStatus::EN_ROUTE->value);
    }
}
