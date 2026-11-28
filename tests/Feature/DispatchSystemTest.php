<?php

namespace Tests\Feature;

use App\Enums\DispatchPriority;
use App\Enums\DispatchStatus;
use App\Enums\GuardDutyType;
use App\Jobs\EnrichPatriotDispatch;
use App\Livewire\Dispatch\DispatcherBoard;
use App\Livewire\Dispatch\ResponseDeployments;
use App\Livewire\Guard\MobileDashboard;
use App\Livewire\Settings\BranchIndex;
use App\Livewire\Sites\SiteProfile;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\ClientAccount;
use App\Models\DispatchEvent;
use App\Models\DispatchStandbyPoint;
use App\Models\DispatchTeam;
use App\Models\EquipmentAsset;
use App\Models\Guard;
use App\Models\OperationalAlert;
use App\Models\PatriotAccountMapping;
use App\Models\Site;
use App\Models\SosAlert;
use App\Models\Tenant;
use App\Models\User;
use App\Services\DispatchService;
use App\Services\PatriotDispatchPayload;
use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Bus\UniqueLock;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use RuntimeException;
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
            ->assertSee('New dispatch')
            ->assertSee(route('dispatch.deployments'))
            ->assertDontSee('Manage response teams');
    }

    public function test_regions_and_response_settings_are_tabs_in_dispatch_deployments(): void
    {
        $this->actingAs($this->admin)->get(route('settings.branches', ['tab' => 'teams']))
            ->assertRedirect(route('dispatch.deployments', ['tab' => 'teams']));

        $this->get(route('billing.regions-response', ['tab' => 'teams']))
            ->assertRedirect(route('dispatch.deployments', ['tab' => 'teams']));

        $this->get(route('dispatch.deployments', ['tab' => 'teams']))
            ->assertOk()
            ->assertSee('Dispatch duty')
            ->assertSee('Standby points')
            ->assertSee('Add team');
    }

    public function test_regions_allow_multiple_blank_codes_and_validate_duplicate_codes(): void
    {
        $settings = Livewire::actingAs($this->admin)->test(BranchIndex::class);
        foreach (['No-code North', 'No-code South'] as $name) {
            $settings->call('openCreate')->set('form.name', $name)->call('save')->assertHasNoErrors();
            $this->assertDatabaseHas('branches', ['tenant_id' => $this->tenant->id, 'name' => $name, 'code' => null]);
        }

        $code = Branch::forTenant($this->tenant->id)->whereNotNull('code')->value('code');
        $settings->call('openCreate')->set('form.name', 'Duplicate code')
            ->set('form.code', $code)->call('save')->assertHasErrors(['form.code' => 'unique']);
    }

    public function test_repeated_critical_patriot_intake_creates_one_pending_alarm(): void
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
                ['jobID' => 2021, 'ticketNo' => 501, 'status' => 'DispatchRequest', 'alarmPriority' => 1],
                ['jobID' => 2022, 'ticketNo' => 501, 'status' => 'DispatchRequest', 'alarmPriority' => 1],
            ]),
        ]);

        $this->artisan('guardops:patriot-import')->assertSuccessful();
        $this->artisan('guardops:patriot-import')->assertSuccessful();
        $this->assertSame(2, DispatchEvent::forTenant($this->tenant->id)->whereIn('patriot_job_id', [2021, 2022])->count());
        $alert = OperationalAlert::forTenant($this->tenant->id)->where('event_type', 'patriot.intake')->sole();

        $this->assertSame('critical', $alert->severity);
        $this->assertNull($alert->acknowledged_at);
        $this->actingAs($this->admin)->get(route('dispatch.control-room'))->assertOk()->assertSee('Critical Patriot alarm');

        $dispatches = app(DispatchService::class);
        $first = DispatchEvent::forTenant($this->tenant->id)->where('patriot_job_id', 2021)->sole();
        $second = DispatchEvent::forTenant($this->tenant->id)->where('patriot_job_id', 2022)->sole();
        $dispatches->setStatus($first, DispatchStatus::CANCELLED, $this->admin->id);
        $this->assertNull($alert->fresh()->resolved_at);
        $this->assertSame(route('dispatch.control-room', ['dispatch' => $second->id], absolute: false), $alert->fresh()->action_url);

        $dispatches->setStatus($second, DispatchStatus::CANCELLED, $this->admin->id);
        $this->assertNotNull($alert->fresh()->resolved_at);
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

        $site = Site::firstOrFail();
        $site->forceFill(['patriot_client_ref' => '1234'])->save();

        $this->artisan('guardops:patriot-import', ['--job-id' => '2017'])->assertSuccessful();
        $this->artisan('guardops:patriot-import', ['--job-id' => '2017'])->assertSuccessful();

        $imported = DispatchEvent::where('patriot_job_id', 2017);
        $this->assertSame(1, $imported->count());
        $dispatch = $imported->firstOrFail();
        $this->assertSame(DispatchStatus::OPEN, $dispatch->status);
        $this->assertSame(DispatchPriority::LOW, $dispatch->priority);
        $dispatch->update(['priority' => DispatchPriority::NORMAL]);
        $this->artisan('guardops:patriot-import', ['--job-id' => '2017'])->assertSuccessful();
        $this->assertSame(DispatchPriority::LOW, $dispatch->fresh()->priority);
        $this->assertNull($dispatch->guard_id);
        $this->assertEquals($site->id, $dispatch->site_id);

        Livewire::actingAs($this->admin)->test(DispatcherBoard::class)
            ->call('selectDispatch', $dispatch->id)
            ->assertSee('Patriot job #2017')
            ->assertSee(route('tracking.live', ['lat' => 5.6, 'lng' => -0.18, 'dispatch' => $dispatch->id]))
            ->assertSee('Customer verified');

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
            ->assertSee('Site details')
            ->assertDontSee('Patriot synchronization')
            ->assertDontSee('Download diagnostics')
            ->assertDontSee('Manage site link');
        $this->artisan('guardops:patriot-import', ['--job-id' => '2018'])->assertSuccessful();
        $this->assertDatabaseHas('dispatch_events', [
            'tenant_id' => $this->tenant->id,
            'patriot_job_id' => 2018,
            'site_id' => $site->id,
            'guard_id' => null,
        ]);
    }

    public function test_new_patriot_account_is_provisioned_from_the_job_before_enrichment(): void
    {
        config(['services.patriot.url' => 'https://patriot.example.test',
            'services.patriot.username' => 'recipient', 'services.patriot.password' => 'test',
            'services.patriot.tenant' => $this->tenant->slug, 'services.patriot.enrichment_enabled' => false]);
        Http::preventStrayRequests();
        Http::fake([
            'https://patriot.example.test/api/Account/Token' => Http::response(['access_token' => 'test-token']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs' => Http::response([
                ['jobID' => 801, 'clientRef' => 88, 'clientName' => 'New account', 'location' => '1 Main Street', 'status' => 'DispatchRequest'],
                ['jobID' => 802, 'clientRef' => 88, 'clientName' => 'New account', 'location' => '1 Main Street', 'status' => 'DispatchRequest'],
            ]),
        ]);

        $this->artisan('guardops:patriot-import')->assertSuccessful();
        $this->artisan('guardops:patriot-import')->assertSuccessful();

        $first = DispatchEvent::where('patriot_job_id', 801)->firstOrFail();
        $second = DispatchEvent::where('patriot_job_id', 802)->firstOrFail();
        $this->assertNotNull($first->site_id);
        $this->assertEquals($first->site_id, $second->site_id);
        $this->assertEquals($first->site->client_account_id, $first->client_account_id);
        $this->assertTrue($first->mayAssignFromPatriot());
        $this->assertDatabaseCount('patriot_account_mappings', 1);
    }

    public function test_enrichment_reuses_a_customer_across_sites_and_a_site_across_base_account_areas(): void
    {
        $this->configurePatriotEnrichment();
        config(['services.patriot.group_by_billing_account' => true]);
        $customerCount = ClientAccount::count();
        $siteCount = Site::count();
        Http::fake([
            'https://patriot.example.test/api/Account/Token' => Http::response(['access_token' => 'test-token']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs' => Http::response([
                $this->patriotJob(900, 90), $this->patriotJob(901, 91), $this->patriotJob(902, 92),
            ]),
            'https://patriot.example.test/api/v3/clients/90' => Http::response($this->patriotClient(90, 'MAIN', '1 Main Street')),
            'https://patriot.example.test/api/v3/clients/91' => Http::response($this->patriotClient(91, 'ANNEX', '2 Main Street')),
            'https://patriot.example.test/api/v3/clients/92' => Http::response($this->patriotClient(92, 'SUB', '1 Main Street') + ['baseAcct' => 'MAIN']),
        ]);

        $this->artisan('guardops:patriot-import')->assertSuccessful();
        Bus::assertDispatchedTimes(EnrichPatriotDispatch::class, 3);
        foreach ([900, 901, 902] as $jobId) {
            $this->enrichPatriotDispatch($jobId);
        }

        [$main, $annex, $sub] = DispatchEvent::whereIn('patriot_job_id', [900, 901, 902])->orderBy('patriot_job_id')->get();
        $this->assertDatabaseCount('client_accounts', $customerCount + 1);
        $this->assertDatabaseCount('sites', $siteCount + 2);
        $this->assertDatabaseCount('patriot_account_mappings', 3);
        $this->assertSame($main->client_account_id, $annex->client_account_id);
        $this->assertNotSame($main->site_id, $annex->site_id);
        $this->assertSame($main->site_id, $sub->site_id);
        $this->assertSame('synchronized', $sub->patriot_sync_status);
        $this->assertNull($main->latitude);
        $this->assertTrue($main->mayAssignFromPatriot());

        Livewire::actingAs($this->admin)->test(DispatcherBoard::class)
            ->call('selectDispatch', $main->id)
            ->assertSee('Customer synchronized')->assertSee('Site linked');
    }

    public function test_lookup_failure_keeps_the_request_assignable_and_retry_preserves_local_edits(): void
    {
        $this->configurePatriotEnrichment();
        Http::fake([
            'https://patriot.example.test/api/Account/Token' => Http::response(['access_token' => 'test-token']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs' => Http::response([
                $this->patriotJob(910, 99) + ['clientName' => 'Dispatch customer', 'location' => '1 Main Street'],
            ]),
            'https://patriot.example.test/api/v3/clients/99' => Http::sequence()
                ->pushStatus(503)->push($this->patriotClient(99, 'MAIN', '1 Main Street')),
        ]);
        $this->artisan('guardops:patriot-import')->assertSuccessful();
        $event = DispatchEvent::where('patriot_job_id', 910)->firstOrFail();
        $this->assertTrue($event->mayAssignFromPatriot());
        $this->assertNull($event->site_id);

        try {
            $this->enrichPatriotDispatch(910);
            $this->fail('The enrichment error must reach the queue worker for retry.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Patriot client lookup failed (HTTP 503).', $exception->getMessage());
        }

        $event->refresh();
        (new EnrichPatriotDispatch($this->tenant->id, $event->id))->failed(new RuntimeException('Patriot timed out.'));
        $event->refresh();
        $this->assertSame('failed', $event->patriot_sync_status);
        $this->assertNotNull($event->site_id);
        $this->assertTrue($event->mayAssignFromPatriot());
        $officer = Guard::firstOrFail();
        $officer->update(['duty_type' => GuardDutyType::Dispatch, 'status' => 'active']);
        app(DispatchService::class)->assignGuard($event, $officer->id, $this->admin->id);
        $event->refresh();
        $this->assertSame(DispatchStatus::ASSIGNED, $event->status);
        $event->site->update(['name' => 'Local site', 'instructions' => 'Local instructions']);
        $event->clientAccount->update(['name' => 'Local customer']);
        $event->update(['internal_notes' => 'Local notes']);

        $this->enrichPatriotDispatch(910);
        $event->refresh();
        $this->assertSame('review', $event->patriot_sync_status);
        $this->assertSame('Local site', $event->site->name);
        $this->assertSame('Local instructions', $event->site->instructions);
        $this->assertSame('Local customer', $event->clientAccount->name);
        $this->assertSame('Local notes', $event->internal_notes);
        $this->assertNotNull(PatriotAccountMapping::sole()->synchronized_at);
    }

    public function test_incomplete_location_requires_verification_and_administrator_can_reconcile(): void
    {
        $this->configurePatriotEnrichment();
        Http::fake([
            'https://patriot.example.test/api/Account/Token' => Http::response(['access_token' => 'test-token']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs' => Http::response([
                $this->patriotJob(920, 121) + ['city' => 'Accra'],
            ]),
            'https://patriot.example.test/api/v3/clients/121' => Http::response(['externalRefNo' => 121]),
        ]);
        $this->artisan('guardops:patriot-import')->assertSuccessful();
        $this->enrichPatriotDispatch(920);
        $event = DispatchEvent::where('patriot_job_id', 920)->firstOrFail();
        $this->assertNull($event->site_id);
        $this->assertFalse($event->mayAssignFromPatriot());
        $site = Site::firstOrFail();
        $site->update(['address' => '1 Verified Street']);

        Livewire::actingAs($this->admin)->test(DispatcherBoard::class)
            ->call('selectDispatch', $event->id)
            ->assertSee('Verify the response location')
            ->set('patriotSiteId', $site->id)
            ->call('linkPatriotSite')
            ->assertHasNoErrors()
            ->assertSee('Customer verified');

        $this->assertTrue($event->fresh()->mayAssignFromPatriot());
        $this->assertEquals($site->id, PatriotAccountMapping::sole()->site_id);

        $event->update(['patriot_sync_status' => 'review']);
        Livewire::actingAs($this->admin)->test(DispatcherBoard::class)
            ->call('selectDispatch', $event->id)
            ->assertSee('Site linked')
            ->assertDontSee('Reconcile with a verified site');
    }

    public function test_patriot_contact_diagnostics_are_allowlisted_and_hidden_from_model_serialization(): void
    {
        $this->configurePatriotEnrichment();
        config(['services.patriot.sync_response_contacts' => true]);
        Http::fake([
            'https://patriot.example.test/api/Account/Token' => Http::response(['access_token' => 'test-token']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs' => Http::response([$this->patriotJob(930, 123)]),
            'https://patriot.example.test/api/v3/clients/123' => Http::response(
                $this->patriotClient(123, 'MAIN', '1 Main Street') + ['sitePhone' => '555-1234', 'accountType' => 'Standard'],
            ),
            'https://patriot.example.test/api/v3/userToClients/search?*' => Http::response([
                'results' => [[
                    'globalDetails' => ['userName' => 'Response contact', 'phoneNo' => '555-9999', 'telePin' => 98765],
                    'siteDetails' => ['callOrder' => 1, 'pin' => 'secret'],
                ]],
                'metadata' => ['hasMore' => true],
            ]),
        ]);

        $this->artisan('guardops:patriot-import')->assertSuccessful();
        $this->enrichPatriotDispatch(930);

        $mapping = PatriotAccountMapping::sole();
        $this->assertSame([
            'account_type' => 'Standard', 'site_phone' => '555-1234', 'contact_sync_status' => 'partial',
            'contacts' => [['name' => 'Response contact', 'telephone' => '555-9999', 'call_order' => 1]],
        ], $mapping->response_details);
        $this->assertArrayNotHasKey('response_details', $mapping->toArray());
        $this->assertStringNotContainsString('555-9999', $mapping->getRawOriginal('response_details'));
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'callListOnly=true'));

        $event = DispatchEvent::where('patriot_job_id', 930)->firstOrFail();
        (new UniqueLock(Cache::store()))->release(new EnrichPatriotDispatch($event->tenant_id, $event->id));
        Livewire::actingAs($this->admin)->test(DispatcherBoard::class)
            ->call('selectDispatch', $event->id)
            ->assertSee('Patriot response contacts')->assertSee('Response contact')->assertSee('555-9999')
            ->call('refreshPatriotAccount')->assertHasNoErrors();
        Bus::assertDispatched(EnrichPatriotDispatch::class, fn (EnrichPatriotDispatch $job): bool => $job->dispatchId === $event->id && $job->refresh);
    }

    public function test_invalid_patrol_response_and_unauthorized_poll_create_no_dispatches(): void
    {
        $this->configurePatriotEnrichment();
        Http::fake([
            'https://patriot.example.test/api/Account/Token' => Http::sequence()->pushStatus(401)
                ->push(['access_token' => 'test-token']),
            'https://patriot.example.test/api/v3/patrolResponse/jobs' => Http::response([
                ['jobID' => 'not-an-integer', 'status' => 'DispatchRequest'],
            ]),
        ]);

        $this->artisan('guardops:patriot-import')->assertFailed();
        $this->artisan('guardops:patriot-import')->assertFailed();
        $this->assertFalse(DispatchEvent::whereNotNull('patriot_job_id')->exists());
        Bus::assertNotDispatched(EnrichPatriotDispatch::class);
    }

    private function configurePatriotEnrichment(): void
    {
        config([
            'services.patriot.url' => 'https://patriot.example.test',
            'services.patriot.username' => 'recipient',
            'services.patriot.password' => 'test',
            'services.patriot.tenant' => $this->tenant->slug,
            'services.patriot.enrichment_enabled' => true,
        ]);
        Bus::fake([EnrichPatriotDispatch::class]);
        Http::preventStrayRequests();
    }

    private function enrichPatriotDispatch(int $jobId): void
    {
        $event = DispatchEvent::where('patriot_job_id', $jobId)->firstOrFail();
        app()->call([(new EnrichPatriotDispatch($this->tenant->id, $event->id)), 'handle']);
    }

    /** @return array{jobID: int, clientRef: int, status: string} */
    private function patriotJob(int $jobId, int $reference): array
    {
        return ['jobID' => $jobId, 'clientRef' => $reference, 'status' => 'DispatchRequest'];
    }

    /** @return array<string, mixed> */
    private function patriotClient(int $reference, string $number, string $address): array
    {
        return [
            'externalRefNo' => $reference,
            'clientNo' => $number,
            'accountNo' => 'BILLING-1',
            'name' => 'Monitored premises',
            'location' => ['lineOne' => $address],
        ];
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

        $this->assertSame(DispatchStatus::CANCELLED, $open->fresh()->status);
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
        $originalSiteRegion = $site->dispatch_region_id;
        $settings = Livewire::actingAs($this->admin)->test(BranchIndex::class, ['section' => 'standby'])
            ->assertSee('Add standby point')
            ->set('pointRegionId', $region->id)
            ->set('pointName', 'North response station')
            ->set('pointLatitude', '91')
            ->set('pointLongitude', '-0.19')
            ->call('createStandbyPoint')
            ->assertHasErrors('pointLatitude')
            ->set('pointLatitude', '5.62')
            ->set('pointRadius', '150')
            ->call('createStandbyPoint')
            ->assertHasNoErrors();
        $point = DispatchStandbyPoint::where('name', 'North response station')->sole();
        $settings->set('teamName', 'Response One')
            ->set('standbyPointId', $point->id)
            ->set('teamMembers', $officers->modelKeys())
            ->call('createDispatchTeam')
            ->assertHasNoErrors();
        $this->assertSame($region->id, $point->region_id);
        $this->assertSame($originalSiteRegion, $site->fresh()->dispatch_region_id);

        $board = Livewire::actingAs($this->admin)->test(DispatcherBoard::class)
            ->assertDontSee('Create response team');
        $team = DispatchTeam::where('name', 'Response One')->sole();
        $this->assertSame(2, $team->members()->count());
        $this->assertSame($point->id, $team->standby_point_id);
        $lead = $officers->first(fn (Guard $officer) => $officer->user_id !== null);
        $officerUser = $lead->user;
        $holder = $officers->first(fn (Guard $officer) => $officer->id !== $lead->id);
        $asset = EquipmentAsset::create([
            'tenant_id' => $this->tenant->id, 'name' => 'Response radio', 'asset_tag' => 'RESPONSE-TEST', 'status' => 'available',
        ]);
        $deployments = Livewire::actingAs($this->admin)->test(ResponseDeployments::class)
            ->set('teamId', $team->id)->set('officers', $officers->modelKeys())
            ->set('custodian', $lead->id)->set('location', 'Control room')
            ->set('end', now()->addHours(8)->format('Y-m-d\TH:i'))
            ->call('addKitLine')->assertSee('Issue to')
            ->set('kit.0.asset_id', $asset->id)->set('kit.0.quantity', '1')->set('kit.0.condition', 'Good')
            ->set('kit.0.guard_id', $holder->id)
            ->call('addKitLine')->set('kit.1', ['asset_id' => $asset->id, 'guard_id' => $lead->id, 'quantity' => 1, 'condition' => 'Fair'])
            ->call('deploy')->assertHasErrors('deployment')->assertSee('can only be issued once to one officer')
            ->call('removeKitLine', 1)
            ->call('deploy')->assertHasNoErrors()->assertSee('En route to standby')->assertSee('Issued kit (1)');
        $deployment = $team->activeDeployment()->sole();
        $item = $deployment->kit()->sole();
        $this->assertSame($holder->id, $item->guard_id);
        $this->assertSame('issued', $asset->fresh()->status->value);
        $backup = Guard::whereKeyNot($officers->first()->id)->whereKeyNot($officers->last()->id)->firstOrFail();
        $backupTeam = DispatchTeam::create(['tenant_id' => $this->tenant->id, 'name' => 'Backup response', 'standby_point_id' => $point->id]);
        $backup->forceFill(['status' => 'active', 'duty_type' => GuardDutyType::Dispatch, 'dispatch_team_id' => $backupTeam->id])->save();
        $backupForm = Livewire::actingAs($this->admin)->test(ResponseDeployments::class)
            ->set('teamId', $backupTeam->id)->set('officers', [$backup->id])->set('custodian', $backup->id)
            ->set('location', 'Control room')->set('end', now()->addHours(8)->format('Y-m-d\TH:i'))
            ->set('kit', [['asset_id' => $asset->id, 'quantity' => 1, 'condition' => 'Good', 'guard_id' => $holder->id]])
            ->call('deploy')->assertHasErrors('deployment')->assertSee('officer reporting for this duty')
            ->set('kit.0.guard_id', $backup->id)
            ->call('deploy')->assertHasErrors('deployment')->assertSee('is not available to issue');
        $this->assertFalse($backupTeam->activeDeployment()->exists());
        $backupForm->set('kit', [])->call('deploy');
        $this->assertSame([], $backupForm->errors()->all());
        $backupDeployment = $backupTeam->activeDeployment()->sole();
        $backupForm->set('arrivalReasons.'.$backupDeployment->id, 'Arrival verified by Control')
            ->call('confirmArrival', $backupDeployment->id)->assertHasNoErrors();
        Livewire::actingAs($officerUser)->test(MobileDashboard::class)
            ->assertSee('North response station')
            ->assertDontSee('My issued kit')
            ->assertSee('5.62, -0.19')
            ->set('latitude', 5.7)->set('longitude', -0.19)->call('confirmStandbyArrival')->assertHasErrors('action')
            ->set('latitude', 5.62)->call('confirmStandbyArrival')->assertHasNoErrors()->assertSee('Ready at standby');
        Livewire::actingAs($holder->user)->test(MobileDashboard::class)
            ->assertSee('My issued kit')->assertSee('Response radio');
        $this->actingAs($this->admin);

        $event = DispatchEvent::create(['event_type' => 'alarm', 'site_id' => $site->id, 'status' => DispatchStatus::OPEN, 'dispatch_number' => 'TEAM-TEST']);
        Exceptions::fake();
        $broadcaster = \Mockery::mock(Broadcaster::class);
        $broadcaster->shouldReceive('broadcast')->withArgs(fn ($channels, $event) => $event === 'dispatch.updated')
            ->once()->andThrow(new BroadcastException('Test broadcaster unavailable.'));
        $broadcaster->shouldReceive('broadcast')->withArgs(fn ($channels, $event) => $event !== 'dispatch.updated')->andReturnNull();
        Broadcast::extend('failing-dispatch-test', fn () => $broadcaster);
        config([
            'broadcasting.default' => 'failing-dispatch-test',
            'broadcasting.connections.failing-dispatch-test' => ['driver' => 'failing-dispatch-test'],
        ]);
        $board->call('selectDispatch', $event->id)->set('assignTeamId', $team->id)
            ->call('assignDispatchTeam')->assertHasNoErrors()->assertSee('Dispatch assigned to the response team.');
        Exceptions::assertReported(BroadcastException::class);
        $this->assertTrue($officerUser->notifications()->where('data->title', 'Dispatch assigned: TEAM-TEST')->exists());
        config(['broadcasting.default' => 'null']);
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
        $deployments->call('closeDuty', $deployment->id)->assertHasErrors('deployment');
        $settings->call('removeDispatchTeam', $team->id)->assertHasErrors('responseTeam');
        $settings->call('removeStandbyPoint', $point->id)->assertHasErrors('standbyPoint');
        $this->assertModelExists($point);

        $board->call('confirmTeamReturn')->assertHasNoErrors();
        $this->assertNotNull($event->fresh()->returned_at);
        $this->assertFalse($team->outstandingDispatches()->exists());
        $this->assertFalse($service->myActiveDispatches($officers->last()->id)->contains('id', $event->id));
        $handover = DispatchEvent::create(['event_type' => 'alarm', 'site_id' => $site->id, 'status' => DispatchStatus::OPEN, 'dispatch_number' => 'HANDOVER-TEST']);
        $service->assignTeam($handover, $team->id, $this->admin->id);
        $deployments->set('callHandovers.'.$handover->id.'.team', $backupTeam->id)
            ->set('callHandovers.'.$handover->id.'.reason', 'Shift change')
            ->call('handOverCall', $handover->id)->assertHasNoErrors();
        $this->assertSame($backupDeployment->id, $handover->fresh()->response_deployment_id);
        $this->assertFalse($team->outstandingDispatches()->exists());
        $deployments->call('beginHandover', $deployment->id)->assertHasNoErrors()->assertSee('Ending duty')
            ->assertSee('Receive kit at Control room')->assertSee('Close duty')
            ->set('returns.'.$deployment->id.'.'.$item->id.'.outcome', 'damaged')
            ->set('returns.'.$deployment->id.'.'.$item->id.'.notes', 'Broken antenna')
            ->call('closeDuty', $deployment->id)->assertHasNoErrors()->assertSee('Select team');
        $this->assertNotNull($deployment->fresh()->closed_at);
        $this->assertSame($holder->id, $item->fresh()->guard_id);
        $this->assertSame('maintenance', $asset->fresh()->status->value);
        $service->setStatus($handover->fresh(), DispatchStatus::CANCELLED, $this->admin->id);
        $service->confirmStationReturn($handover->fresh(), $this->admin->id);
        $backupForm->call('beginHandover', $backupDeployment->id)->assertHasNoErrors()
            ->call('closeDuty', $backupDeployment->id)->assertHasNoErrors();
        $settings->call('removeStandbyPoint', $point->id)->assertHasNoErrors();
        $this->assertModelMissing($point);
        $this->assertNull($team->fresh()->standby_point_id);
        $settings->call('editDispatchTeam', $team->id)
            ->set('teamName', 'Response Two')
            ->set('teamMembers', [(string) $officers->first()->id])
            ->call('createDispatchTeam')->assertHasNoErrors();
        $this->assertSame('Response Two', $team->fresh()->name);
        $this->assertNull($officers->last()->fresh()->dispatch_team_id);
        $settings->call('removeDispatchTeam', $team->id)->assertHasNoErrors();
        $this->assertSoftDeleted($team);
        $this->assertNull($officers->first()->fresh()->dispatch_team_id);
        $this->assertSame($team->id, $event->fresh()->dispatchTeam->id);
    }

    public function test_settings_manager_can_edit_a_standby_point(): void
    {
        config(['services.geocoding.url' => 'https://geocoder.example.test']);
        Http::preventStrayRequests();
        Http::fake(['https://geocoder.example.test/reverse?*' => Http::response(['display_name' => 'North Road, Accra'])]);
        $region = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'North Region', 'is_active' => true]);
        $point = DispatchStandbyPoint::create([
            'tenant_id' => $this->tenant->id,
            'region_id' => $region->id,
            'name' => 'Old response station',
            'latitude' => 5.62,
            'longitude' => -0.19,
        ]);

        Livewire::actingAs($this->admin)->test(BranchIndex::class)
            ->call('editStandbyPoint', $point->id)
            ->assertSet('editingStandbyPointId', $point->id)
            ->set('pointName', 'North response station')
            ->set('pointLatitude', '5.64')
            ->set('pointRadius', '150')
            ->call('lookupStandbyAddress')
            ->assertSet('pointAddress', 'North Road, Accra')
            ->call('createStandbyPoint')
            ->assertHasNoErrors()
            ->assertSee('Standby point updated.');

        $this->assertDatabaseHas('dispatch_standby_points', [
            'id' => $point->id,
            'name' => 'North response station',
            'latitude' => 5.64,
            'radius_meters' => 150,
            'address' => 'North Road, Accra',
            'longitude' => -0.19,
        ]);
    }

    public function test_checkout_splits_stock_between_officers_and_checks_combined_quantity(): void
    {
        $region = Branch::create(['tenant_id' => $this->tenant->id, 'name' => 'Checkout test region', 'is_active' => true]);
        $point = DispatchStandbyPoint::create([
            'tenant_id' => $this->tenant->id, 'name' => 'Checkout standby', 'region_id' => $region->id,
            'latitude' => 5.63, 'longitude' => -0.18, 'radius_meters' => 150,
        ]);
        $team = DispatchTeam::create(['tenant_id' => $this->tenant->id, 'name' => 'Checkout team', 'standby_point_id' => $point->id]);
        $officers = Guard::forTenant($this->tenant->id)->limit(2)->pluck('id')->all();
        Guard::forTenant($this->tenant->id)->whereKey($officers)->update([
            'dispatch_team_id' => $team->id, 'duty_type' => GuardDutyType::Dispatch->value, 'status' => 'active',
        ]);
        $category = AssetCategory::create(['tenant_id' => $this->tenant->id, 'name' => 'Checkout stock', 'type' => 'consumable']);
        $stock = EquipmentAsset::create([
            'tenant_id' => $this->tenant->id, 'name' => 'Duty shirts', 'asset_category_id' => $category->id,
            'quantity_on_hand' => 2, 'status' => 'available',
        ]);
        $lines = array_map(fn ($officerId) => ['asset_id' => $stock->id, 'guard_id' => $officerId, 'quantity' => 2, 'condition' => 'Good'], $officers);
        $form = Livewire::actingAs($this->admin)->test(ResponseDeployments::class)
            ->set('teamId', $team->id)->set('officers', $officers)->set('custodian', $officers[0])
            ->set('location', 'Control room')->set('end', now()->addHours(8)->format('Y-m-d\TH:i'))->set('kit', $lines)
            ->call('deploy')->assertHasErrors('deployment')->assertSee('only 2 available across all checkout lines');
        $this->assertSame(2, $stock->fresh()->quantity_on_hand);
        $this->assertFalse($team->activeDeployment()->exists());

        $form->set('kit.0.quantity', 1)->set('kit.1.quantity', 1)->set('kit.1.guard_id', (string) $officers[0])
            ->call('deploy')->assertHasErrors('deployment')->assertSee('listed more than once for the same officer');
        $this->assertSame(2, $stock->fresh()->quantity_on_hand);
        $this->assertFalse($team->activeDeployment()->exists());

        $form->set('kit.1.guard_id', $officers[1])->call('deploy')->assertHasNoErrors();
        $deployment = $team->activeDeployment()->sole();
        $items = $deployment->kit()->orderBy('id')->get();
        $this->assertSame($officers, $items->pluck('guard_id')->all());
        $this->assertSame(0, $stock->fresh()->quantity_on_hand);

        $form->call('beginHandover', $deployment->id)
            ->set('returns.'.$deployment->id.'.'.$items[0]->id.'.outcome', 'returned')
            ->set('returns.'.$deployment->id.'.'.$items[1]->id, ['outcome' => 'consumed', 'notes' => 'Used during duty'])
            ->call('closeDuty', $deployment->id)->assertHasNoErrors();
        $this->assertSame(1, $stock->fresh()->quantity_on_hand);
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

        $this->expectException(RuntimeException::class);
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
            ->assertSee('Updating dispatch queue', false)
            ->assertSee('role="status"', false)
            ->assertSee('wire:loading.delay', false)
            ->assertSee('pointer-events-none absolute inset-x-0 top-0', false);
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
