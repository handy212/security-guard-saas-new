<?php

namespace Tests\Feature;

use App\Enums\DispatchStatus;
use App\Enums\GuardDutyType;
use App\Livewire\Dispatch\DispatcherBoard;
use App\Livewire\Guard\MobileDashboard;
use App\Livewire\Settings\BranchIndex;
use App\Livewire\Sites\SiteProfile;
use App\Models\Branch;
use App\Models\ClientAccount;
use App\Models\DispatchEvent;
use App\Models\DispatchTeam;
use App\Models\Guard;
use App\Models\Site;
use App\Models\SosAlert;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DispatchService;
use App\Services\PatriotDispatchPayload;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_patriot_jobs_are_imported_once_and_reuse_the_site_mapping(): void
    {
        config(['services.patriot' => [
            'url' => 'https://patriot.example.test',
            'username' => 'test-recipient',
            'password' => 'test-password',
            'tenant' => $this->tenant->slug,
        ]]);
        Http::preventStrayRequests();
        Http::fake([
            'https://patriot.example.test/api/Account/Token' => Http::response(['access_token' => 'test-token']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs' => Http::response([
                [
                    'jobID' => 2017,
                    'clientRef' => 1234,
                    'status' => 'DispatchRequest',
                    'ticketNo' => 501,
                    'clientNo' => 'ACC001245',
                    'clientName' => 'Example Security Client',
                    'alarmDescription' => 'Test alarm',
                    'alarmPriority' => 4,
                    'dispatchTime' => '2026-11-23T15:30:00+01:00',
                    'latitude' => 5.6,
                    'longitude' => -0.18,
                    'dispatchInstructions' => 'Inspect and report.',
                    'jobNotes' => 'Use the main entrance.',
                    'clientUserPin' => 'test-secret-pin',
                ],
                ['jobID' => 2018, 'clientRef' => 1234, 'status' => 'DispatchRequest'],
            ]),
        ]);

        $this->artisan('guardops:patriot-import', ['--job-id' => '2017'])->assertSuccessful();
        $this->artisan('guardops:patriot-import', ['--job-id' => '2017'])->assertSuccessful();

        $imported = DispatchEvent::where('patriot_job_id', 2017);
        $this->assertSame(1, $imported->count());
        $dispatch = $imported->firstOrFail();
        $this->assertSame(DispatchStatus::OPEN, $dispatch->status);
        $this->assertNull($dispatch->guard_id);
        $this->assertNull($dispatch->site_id);

        $site = Site::firstOrFail();
        Livewire::actingAs($this->admin)->test(DispatcherBoard::class)
            ->assertSee('Needs site mapping')
            ->call('selectDispatch', $dispatch->id)
            ->assertSee('Patriot job #2017')
            ->set('patriotSiteId', $site->id)
            ->call('linkPatriotSite')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('dispatch_events', ['id' => $dispatch->id, 'site_id' => $site->id, 'guard_id' => null]);
        $payload = app(PatriotDispatchPayload::class)->forDispatch($dispatch->fresh());
        $this->assertSame(501, $payload['activation_id']);
        $this->assertSame('ACC001245', $payload['account_number']);
        $this->assertSame($site->name, $payload['site_name']);
        $this->assertSame($site->address, $payload['location']['address']);
        $this->assertSame(5.6, $payload['location']['latitude']);
        $this->assertSame('2026-11-23T14:30:00+00:00', $payload['dispatch_time']);
        $this->assertNull($payload['alarm_time']);
        $this->assertSame('Inspect and report.', $payload['instructions']);
        $this->assertStringNotContainsString('test-secret-pin', json_encode($payload));
        $this->assertArrayNotHasKey('patriot_payload', $dispatch->fresh()->toArray());
        Livewire::actingAs($this->admin)->test(DispatcherBoard::class)
            ->call('selectDispatch', $dispatch->id)
            ->assertSee('Activation ticket')
            ->set('search', 'ACC001245')
            ->assertSee('PAT-2017')
            ->set('search', '501')
            ->assertSee('PAT-2017')
            ->set('search', 'missing-account')
            ->assertSee('No matching dispatches')
            ->call('downloadPatriotPayload')
            ->assertFileDownloaded('patriot-2017.json');
        $this->artisan('guardops:patriot-import', ['--job-id' => '2018'])->assertSuccessful();
        $this->assertDatabaseHas('dispatch_events', [
            'tenant_id' => $this->tenant->id,
            'patriot_job_id' => 2018,
            'site_id' => $site->id,
            'guard_id' => null,
        ]);
    }

    public function test_scheduled_import_removes_only_unassigned_requests_missing_from_two_successful_polls(): void
    {
        config(['services.patriot' => [
            'url' => 'https://patriot.example.test',
            'username' => 'test-recipient',
            'password' => 'test-password',
            'tenant' => $this->tenant->slug,
            'polling_enabled' => true,
        ]]);
        Http::preventStrayRequests();
        Http::fake([
            'https://patriot.example.test/api/Account/Token' => Http::response(['access_token' => 'test-token']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs' => Http::sequence()
                ->push([])->push([], 503)->push([['jobID' => 610, 'status' => 'DispatchRequest']])->push([])->push([]),
        ]);
        $open = DispatchEvent::create(['patriot_job_id' => 610, 'event_type' => 'alarm']);
        $assigned = DispatchEvent::create(['patriot_job_id' => 611, 'event_type' => 'alarm', 'status' => DispatchStatus::ASSIGNED]);
        $syncing = DispatchEvent::create(['patriot_job_id' => 612, 'event_type' => 'alarm', 'patriot_pending_action' => 'accept']);

        $this->artisan('guardops:patriot-import', ['--scheduled' => true])->assertSuccessful();
        $this->assertNotNull($open->fresh()->patriot_missing_since);
        $this->travel(2)->minutes();
        $this->artisan('guardops:patriot-import', ['--scheduled' => true])->assertFailed();
        $this->assertNotNull($open->fresh());
        $this->artisan('guardops:patriot-import', ['--scheduled' => true])->assertSuccessful();
        $this->assertNull($open->fresh()->patriot_missing_since);
        $this->artisan('guardops:patriot-import', ['--scheduled' => true])->assertSuccessful();
        $this->travel(2)->minutes();
        $this->artisan('guardops:patriot-import', ['--scheduled' => true])->assertSuccessful();

        $this->assertNull($open->fresh());
        $this->assertNotNull($assigned->fresh());
        $this->assertNotNull($syncing->fresh());
    }

    public function test_patrol_reference_can_be_linked_from_a_site_profile(): void
    {
        $site = Site::firstOrFail();
        $dispatch = DispatchEvent::create([
            'patriot_job_id' => 2100,
            'patriot_client_ref' => '3456',
            'status' => DispatchStatus::OPEN,
            'event_type' => 'alarm',
        ]);

        Livewire::actingAs($this->admin)->test(SiteProfile::class, ['site' => $site])
            ->call('setTab', 'profile')
            ->assertSee('Patriot site link')
            ->set('patriotClientRef', ' 3456 ')
            ->call('savePatriotMapping')
            ->assertHasNoErrors();

        $this->assertSame('3456', $site->fresh()->patriot_client_ref);
        $this->assertEquals($site->id, $dispatch->fresh()->site_id);
    }

    public function test_patriot_decisions_and_field_updates_write_back_in_order(): void
    {
        config(['services.patriot' => [
            'url' => 'https://patriot.example.test',
            'username' => 'test-recipient',
            'password' => 'test-password',
            'tenant' => $this->tenant->slug,
            'writeback_enabled' => true,
        ]]);
        Http::preventStrayRequests();
        Http::fake([
            'https://patriot.example.test/api/Account/Token' => Http::response(['access_token' => 'test-token']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs/501' => Http::sequence()
                ->push(['jobID' => 501, 'status' => 'DispatchRequest'])
                ->push(['jobID' => 501, 'status' => 'DispatchAccepted'])
                ->push(['jobID' => 501, 'status' => 'OnSite']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs/501/accept' => Http::response(),
            'https://patriot.example.test/api/v3/patrolResponse/jobs/501/update' => Http::response([]),
            'https://patriot.example.test/api/v3/patrolResponse/jobs/502' => Http::response(['jobID' => 502, 'status' => 'DispatchRequest']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs/502/release?*' => Http::response(),
        ]);

        $site = Site::firstOrFail();
        $officer = Guard::firstOrFail();
        $officer->update(['duty_type' => GuardDutyType::Dispatch, 'status' => 'active']);
        $first = DispatchEvent::create(['patriot_job_id' => 501, 'status' => DispatchStatus::OPEN, 'site_id' => $site->id, 'event_type' => 'alarm']);
        $second = DispatchEvent::create(['patriot_job_id' => 502, 'status' => DispatchStatus::OPEN, 'site_id' => $site->id, 'event_type' => 'alarm']);
        $board = Livewire::actingAs($this->admin)->test(DispatcherBoard::class);
        $board->call('selectDispatch', $first->id)->assertDontSee('Accept in Patriot')
            ->set('assignGuardId', $officer->id)->call('assignGuard')->assertHasNoErrors()
            ->assertSee('Waiting for the assigned officer to accept or decline.');
        $board->call('selectDispatch', $second->id)->set('assignGuardId', $officer->id)->call('assignGuard')->assertHasNoErrors();
        $mobile = Livewire::actingAs($officer->user)->test(MobileDashboard::class);
        $mobile->assertSee('Accept dispatch')->assertSee('Decline dispatch');
        $mobile->call('respondToPatriotDispatch', $first->id, 'accept')->assertHasNoErrors();
        $mobile->call('respondToPatriotDispatch', $second->id, 'decline')->assertHasNoErrors();
        Http::assertNothingSent();
        $this->assertSame(DispatchStatus::ASSIGNED, $second->fresh()->status);
        $this->assertNotNull($first->fresh()->acknowledged_at);

        $this->artisan('guardops:patriot-send-updates')->assertSuccessful();
        $this->assertSame('accepted', $first->fresh()->patriot_response_state);
        $this->assertSame('declined', $second->fresh()->patriot_response_state);
        $this->assertSame(DispatchStatus::CANCELLED, $second->fresh()->status);

        $service = app(DispatchService::class);
        $service->advanceStatus($first->fresh(), $this->admin->id);
        $service->advanceStatus($first->fresh(), $this->admin->id);
        $this->artisan('guardops:patriot-send-updates')->assertSuccessful();
        $this->assertSame('on_site', $first->fresh()->patriot_response_state);

        $first->update(['action_taken' => 'Area checked.']);
        $service->advanceStatus($first->fresh(), $this->admin->id);
        $this->artisan('guardops:patriot-send-updates')->assertSuccessful();
        $this->assertSame('completed', $first->fresh()->patriot_response_state);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), '/jobs/502/release?'));
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/jobs/501/update')
            && ($request->data()[1]['note'] ?? null) === 'Area checked.');
    }

    public function test_patriot_inbox_can_reconcile_an_acceptance_without_resending_it(): void
    {
        config(['services.patriot' => [
            'url' => 'https://patriot.example.test',
            'username' => 'test-recipient',
            'password' => 'test-password',
            'tenant' => $this->tenant->slug,
            'writeback_enabled' => true,
        ]]);
        Http::preventStrayRequests();
        Http::fake([
            'https://patriot.example.test/api/Account/Token' => Http::response(['access_token' => 'test-token']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs/503' => Http::response(['jobID' => 503, 'status' => 'DispatchAccepted']),
        ]);
        $event = DispatchEvent::create([
            'patriot_job_id' => 503,
            'dispatch_number' => 'PAT-503',
            'event_type' => 'alarm',
            'status' => DispatchStatus::OPEN,
            'patriot_pending_action' => 'accept',
            'patriot_writeback_error' => 'Previous attempt interrupted.',
        ]);
        DispatchEvent::create(['event_type' => 'alarm', 'dispatch_number' => 'MANUAL-TEST']);

        Livewire::actingAs($this->admin)->test(DispatcherBoard::class)
            ->set('statusFilter', 'patriot-attention')
            ->assertSee('PAT-503')
            ->assertDontSee('MANUAL-TEST')
            ->call('selectDispatch', $event->id)
            ->call('reviewPatriotSync', 'check');

        $this->artisan('guardops:patriot-send-updates')->assertSuccessful();
        $this->assertSame('accepted', $event->fresh()->patriot_response_state);
        $this->assertNull($event->fresh()->patriot_pending_action);
        Http::assertSentCount(2);
    }

    public function test_response_team_stays_busy_until_station_return_is_confirmed(): void
    {
        $site = Site::firstOrFail();
        $officers = Guard::limit(2)->get();
        foreach ($officers as $officer) {
            $officer->update(['status' => 'active', 'duty_type' => GuardDutyType::Dispatch]);
        }

        $region = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'Greater Accra', 'is_active' => true]);
        Livewire::actingAs($this->admin)->test(BranchIndex::class)
            ->assertSee('Dispatch standby points')
            ->set('pointRegionId', $region->id)
            ->set('pointSiteId', $site->id)
            ->call('registerStandbyPoint')
            ->assertHasNoErrors()
            ->set('teamName', 'Response One')
            ->set('standbySiteId', $site->id)
            ->set('teamMembers', $officers->modelKeys())
            ->call('createDispatchTeam')
            ->assertHasNoErrors();
        $this->assertEquals($region->id, $site->fresh()->dispatch_region_id);

        $board = Livewire::actingAs($this->admin)->test(DispatcherBoard::class)
            ->assertDontSee('Create response team');
        $team = DispatchTeam::where('name', 'Response One')->sole();
        $this->assertSame(2, $team->members()->count());
        $event = DispatchEvent::create(['event_type' => 'alarm', 'site_id' => $site->id, 'status' => DispatchStatus::OPEN, 'dispatch_number' => 'TEAM-TEST']);
        $board->call('selectDispatch', $event->id)->set('assignTeamId', $team->id)
            ->call('assignDispatchTeam')->assertHasNoErrors();
        $this->assertSame($team->id, $event->fresh()->dispatch_team_id);
        $service = app(DispatchService::class);
        $this->assertTrue($service->myActiveDispatches($officers->last()->id)->contains('id', $event->id));

        $service->acknowledgeTeamDispatch($event->fresh(), $this->admin->id);
        $this->assertNotNull($event->fresh()->acknowledged_at);
        $service->advanceStatus($event->fresh(), $this->admin->id);
        $service->advanceStatus($event->fresh(), $this->admin->id);
        $service->recordTeamOutcome($event->fresh(), 'Site checked and secured.', $this->admin->id);
        $this->assertSame('Site checked and secured.', $event->fresh()->action_taken);
        $this->assertTrue($event->fresh()->awaitingStationReturn());
        $this->assertTrue($team->outstandingDispatches()->exists());

        $board->call('confirmTeamReturn')->assertHasNoErrors();
        $this->assertNotNull($event->fresh()->returned_at);
        $this->assertFalse($team->outstandingDispatches()->exists());
        $this->assertFalse($service->myActiveDispatches($officers->last()->id)->contains('id', $event->id));
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
            ->test(DispatcherBoard::class)
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

    public function test_field_api_only_allows_the_assigned_officer_to_accept_and_hides_internal_notes(): void
    {
        $guardUser = User::where('email', 'john.guard@test')->firstOrFail();
        $guard = $guardUser->guardProfile;
        $site = Site::firstOrFail();
        $event = DispatchEvent::create([
            'tenant_id' => $this->tenant->id,
            'guard_id' => $guard->id,
            'site_id' => $site->id,
            'patriot_job_id' => 775,
            'status' => DispatchStatus::ASSIGNED,
            'event_type' => 'alarm',
            'internal_notes' => 'Control only',
        ]);

        $this->actingAs($this->admin)->postJson("/api/v1/dispatches/{$event->id}/respond", ['decision' => 'accept'])
            ->assertForbidden();
        $this->actingAs($guardUser)->postJson("/api/v1/dispatches/{$event->id}/respond", ['decision' => 'accept'])
            ->assertOk()->assertJsonPath('patriot_pending_action', 'accept')->assertJsonMissingPath('internal_notes');
        $this->getJson('/api/v1/dispatches')->assertOk()->assertJsonMissingPath('0.internal_notes');
        $this->postJson("/api/v1/dispatches/{$event->id}/advance")->assertUnprocessable();
    }

    public function test_create_from_sos_sets_client_account(): void
    {
        $guard = Guard::where('tenant_id', $this->tenant->id)->first();
        $site = Site::where('tenant_id', $this->tenant->id)->first();

        $alert = SosAlert::create([
            'tenant_id' => $this->tenant->id,
            'guard_id' => $guard->id,
            'site_id' => $site->id,
            'latitude' => 5.6,
            'longitude' => -0.18,
            'message' => 'Need backup',
            'status' => 'open',
            'raised_at' => now(),
        ]);

        $event = app(DispatchService::class)->createFromSos($alert, $this->admin->id);

        $this->assertSame($site->client_account_id, $event->client_account_id);
        $this->assertSame(DispatchStatus::OPEN, $event->status);
        $this->assertNull($event->guard_id);
        $this->assertSame('acknowledged', $alert->fresh()->status);
        $this->assertSame($event->id, app(DispatchService::class)->createFromSos($alert, $this->admin->id)->id);
        $this->assertSame('critical', $event->priority->value);

        $service = app(DispatchService::class);
        $service->assignGuard($event, $guard->id, $this->admin->id);
        for ($step = 0; $step < 3; $step++) {
            $service->advanceStatus($event->fresh(), $this->admin->id);
        }
        $this->assertSame('resolved', $alert->fresh()->status);
    }

    public function test_cannot_advance_open_dispatch_without_guard(): void
    {
        $client = ClientAccount::where('tenant_id', $this->tenant->id)->first();
        $site = Site::where('tenant_id', $this->tenant->id)->first();

        $event = app(DispatchService::class)->createDispatch([
            'tenant_id' => $this->tenant->id,
            'client_account_id' => $client->id,
            'site_id' => $site->id,
            'created_by_user_id' => $this->admin->id,
            'event_type' => 'alarm',
            'priority' => 'normal',
            'caller_type' => 'client',
            'caller_name' => 'Front desk',
            'incident_location' => 'Gate',
            'description' => 'Alarm sounding',
        ]);

        $this->assertSame(DispatchStatus::OPEN, $event->status);
        $this->assertNull($event->guard_id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Assign a guard before advancing');

        app(DispatchService::class)->advanceStatus($event, $this->admin->id);
    }

    public function test_livewire_blocks_advance_without_guard(): void
    {
        $client = ClientAccount::where('tenant_id', $this->tenant->id)->first();
        $site = Site::where('tenant_id', $this->tenant->id)->first();

        $event = app(DispatchService::class)->createDispatch([
            'tenant_id' => $this->tenant->id,
            'client_account_id' => $client->id,
            'site_id' => $site->id,
            'created_by_user_id' => $this->admin->id,
            'event_type' => 'alarm',
            'priority' => 'normal',
            'caller_type' => 'client',
            'caller_name' => 'Front desk',
            'incident_location' => 'Gate',
            'description' => 'Alarm sounding',
        ]);

        Livewire::actingAs($this->admin)
            ->test(DispatcherBoard::class)
            ->call('selectDispatch', $event->id)
            ->call('advanceStatus')
            ->assertHasErrors('assignGuardId')
            ->assertSet('selectedId', $event->id)
            ->assertSee('Assign a guard before advancing this dispatch.');

        $this->assertSame(DispatchStatus::OPEN, $event->fresh()->status);
    }

    public function test_assign_then_advance_from_board(): void
    {
        $client = ClientAccount::where('tenant_id', $this->tenant->id)->first();
        $site = Site::where('tenant_id', $this->tenant->id)->first();
        $guard = Guard::where('tenant_id', $this->tenant->id)->where('status', 'active')->first();

        $event = app(DispatchService::class)->createDispatch([
            'tenant_id' => $this->tenant->id,
            'client_account_id' => $client->id,
            'site_id' => $site->id,
            'created_by_user_id' => $this->admin->id,
            'event_type' => 'alarm',
            'priority' => 'high',
            'caller_type' => 'client',
            'caller_name' => 'Front desk',
            'incident_location' => 'Gate',
            'description' => 'Alarm sounding',
        ]);

        Livewire::actingAs($this->admin)
            ->test(DispatcherBoard::class)
            ->call('selectDispatch', $event->id)
            ->assertSet('selectedId', $event->id)
            ->assertSee('aria-pressed="true"', false)
            ->set('assignGuardId', $guard->id)
            ->call('assignGuard')
            ->call('advanceStatus')
            ->assertHasNoErrors();

        $this->assertSame(DispatchStatus::EN_ROUTE, $event->fresh()->status);
    }

    public function test_queue_lists_most_urgent_priority_before_newer_lower_priority(): void
    {
        $client = ClientAccount::where('tenant_id', $this->tenant->id)->first();
        $site = Site::where('tenant_id', $this->tenant->id)->first();

        $payload = [
            'tenant_id' => $this->tenant->id,
            'client_account_id' => $client->id,
            'site_id' => $site->id,
            'created_by_user_id' => $this->admin->id,
            'event_type' => 'alarm',
            'caller_type' => 'client',
            'caller_name' => 'Front desk',
            'incident_location' => 'Gate',
            'description' => 'Alarm sounding',
        ];

        $critical = app(DispatchService::class)->createDispatch($payload + ['priority' => 'critical']);
        $normal = app(DispatchService::class)->createDispatch($payload + ['priority' => 'normal']);

        Livewire::actingAs($this->admin)
            ->test(DispatcherBoard::class)
            ->assertSee('Critical alarm')
            ->assertViewHas('criticalAlert', fn (DispatchEvent $event) => $event->is($critical))
            ->assertViewHas('dispatches', function ($dispatches) use ($critical, $normal) {
                $ids = $dispatches->pluck('id');
                $criticalPosition = $ids->search($critical->id);
                $normalPosition = $ids->search($normal->id);

                return $criticalPosition !== false
                    && $normalPosition !== false
                    && $criticalPosition < $normalPosition
                    && $dispatches->first()->priority->value === 'critical';
            });
    }

    public function test_filtered_empty_queue_explains_how_to_recover(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DispatcherBoard::class)
            ->set('search', 'no matching dispatch exists')
            ->assertSee('No matching dispatches')
            ->assertSee('Try changing or clearing the filters.')
            ->assertSee('Clear filters')
            ->assertDontSee('Create a dispatch when a client or site needs response.');
    }

    public function test_dispatch_board_exposes_accessible_loading_feedback(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DispatcherBoard::class)
            ->assertSee('Updating dispatch view', false)
            ->assertSee('role="status"', false);
    }

    public function test_sos_status_precedes_routine_metrics_and_is_textually_clear(): void
    {
        $guard = Guard::where('tenant_id', $this->tenant->id)->first();
        $site = Site::where('tenant_id', $this->tenant->id)->first();

        SosAlert::create([
            'tenant_id' => $this->tenant->id,
            'guard_id' => $guard->id,
            'site_id' => $site->id,
            'message' => 'Need immediate assistance',
            'status' => 'open',
            'raised_at' => now(),
        ]);

        Livewire::actingAs($this->admin)
            ->test(DispatcherBoard::class)
            ->assertSeeInOrder([
                'SOS alerts require response',
                'Open — response needed',
                'Active',
                'Dispatch queue',
            ])
            ->assertSee('Acknowledge');
    }
}
