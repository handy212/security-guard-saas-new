<?php

namespace Tests\Feature;

use App\Models\Guard;
use App\Models\GuardLocation;
use App\Models\JwmDevice;
use App\Models\JwmDeviceAssignment;
use App\Models\PatrolCheckpoint;
use App\Models\PatrolRoute;
use App\Models\PatrolSession;
use App\Models\ShiftAssignment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PatrolService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class JwmDeviceIngestionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Guard $guard;

    private string $adminToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@demo.test')->firstOrFail();
        app()->instance('currentTenant', $this->admin->tenant);
        setPermissionsTeamId($this->admin->tenant_id);
        $this->guard = Guard::where('tenant_id', $this->admin->tenant_id)->firstOrFail();
        $this->adminToken = $this->admin->createToken('test-admin')->plainTextToken;
        $this->asBearer($this->adminToken);
        $this->travelTo(now()->startOfSecond());
    }

    public function test_admin_can_register_list_and_disable_a_device_without_exposing_credentials(): void
    {
        $id = $this->registerDevice();
        $this->getJson('/api/v1/admin/jwm-devices')
            ->assertOk()
            ->assertJsonPath('data.0.id', $id)
            ->assertJsonPath('data.0.model', 'JWM5000ZG')
            ->assertJsonPath('data.0.require_device_identity', false)
            ->assertJsonMissingPath('data.0.bridge_token_id');

        $this->patchJson("/api/v1/admin/jwm-devices/{$id}", ['is_active' => false])
            ->assertOk()->assertJsonPath('data.is_active', false);
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/bridge-token")
            ->assertUnprocessable();
    }

    public function test_additive_migration_rolls_back_without_changing_existing_guard_data(): void
    {
        $guards = Guard::count();
        $identityMigration = require database_path('migrations/2026_11_16_000001_create_jwm_identity_mappings.php');
        $identityMigration->down();
        $this->assertFalse(Schema::hasTable('jwm_guard_mappings'));
        $this->assertFalse(Schema::hasTable('jwm_checkpoint_mappings'));
        $this->assertFalse(Schema::hasColumn('jwm_devices', 'require_device_identity'));
        $this->assertFalse(Schema::hasColumn('jwm_device_events', 'guard_external_id'));
        $migration = require database_path('migrations/2026_11_13_000001_create_jwm_device_tables.php');
        $migration->down();
        foreach (['jwm_devices', 'jwm_device_assignments', 'jwm_device_events'] as $table) {
            $this->assertFalse(Schema::hasTable($table));
        }
        $this->assertSame($guards, Guard::count());
        $migration->up();
        $identityMigration->up();
        $this->registerDevice();
    }

    public function test_existing_patrol_callers_still_use_server_time_by_default(): void
    {
        $session = $this->patrolSession();
        $scan = app(PatrolService::class)->scanCheckpoint([
            'patrol_session_id' => $session->id, 'checkpoint_code' => 'JWM-CP-01',
            'latitude' => null, 'longitude' => null,
            'scanned_at' => now()->subHour()->toIso8601String(),
        ]);
        $this->assertTrue($scan->scanned_at->equalTo(now()));
        $this->assertSame('completed', $session->fresh()->status);
    }

    public function test_identity_migration_preserves_existing_events_and_legacy_replays(): void
    {
        $id = $this->readyDevice();
        $payload = $this->gpsPayload();
        $eventId = $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertCreated()->json('data.id');
        $migration = require database_path('migrations/2026_11_16_000001_create_jwm_identity_mappings.php');
        $migration->down();
        $this->assertDatabaseHas('jwm_device_events', ['id' => $eventId]);
        $this->assertSame(1, GuardLocation::where('source', 'jwm')->count());
        $migration->up();
        $this->assertFalse(JwmDevice::findOrFail($id)->require_device_identity);
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertOk()->assertJsonPath('data.id', $eventId)->assertJsonPath('replayed', true);
        $this->assertDatabaseCount('jwm_device_events', 1);
        $this->assertSame(1, GuardLocation::where('source', 'jwm')->count());
    }

    public function test_guard_use_intervals_allow_handover_but_reject_overlap_or_wrong_tenant(): void
    {
        $id = $this->registerDevice();
        $start = now()->subHours(2)->toIso8601String();
        $handover = now()->subHour()->toIso8601String();
        $end = now()->addHour()->toIso8601String();
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/assignments", [
            'guard_id' => $this->guard->id, 'starts_at' => $start, 'ends_at' => $handover,
        ])->assertCreated();
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/assignments", [
            'guard_id' => $this->guard->id, 'starts_at' => $handover, 'ends_at' => $end,
        ])->assertCreated();
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/assignments", [
            'guard_id' => $this->guard->id, 'starts_at' => $start, 'ends_at' => $end,
        ])->assertUnprocessable()->assertJsonValidationErrors('starts_at');
        $this->getJson("/api/v1/admin/jwm-devices/{$id}/assignments")
            ->assertOk()->assertJsonCount(2, 'data');
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/assignments", [
            'guard_id' => $this->guard->id, 'starts_at' => $end, 'ends_at' => $end,
        ])->assertUnprocessable()->assertJsonValidationErrors('ends_at');

        $otherTenant = Tenant::create(['name' => 'Other tenant', 'slug' => 'other-jwm', 'status' => 'active']);
        $otherGuard = Guard::create([
            'tenant_id' => $otherTenant->id, 'first_name' => 'Other', 'last_name' => 'Guard',
            'employee_number' => 'OTHER-JWM', 'status' => 'active',
        ]);
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/assignments", [
            'guard_id' => $otherGuard->id,
            'starts_at' => now()->addHours(2)->toIso8601String(),
            'ends_at' => now()->addHours(3)->toIso8601String(),
        ])->assertUnprocessable()->assertJsonValidationErrors('guard_id');
        $this->assertDatabaseCount('jwm_device_assignments', 2);
    }

    public function test_only_authorized_tenant_admin_can_manage_devices(): void
    {
        $id = $this->registerDevice();
        $guardUser = User::where('email', 'john.guard@test')->firstOrFail();
        $this->asBearer($guardUser->createToken('guard')->plainTextToken);
        $this->getJson('/api/v1/admin/jwm-devices')->assertForbidden();

        $other = Tenant::create(['name' => 'Other', 'slug' => 'other-device', 'status' => 'active']);
        $user = User::create([
            'tenant_id' => $other->id, 'name' => 'Other admin', 'email' => 'other-jwm@test',
            'password' => 'password', 'status' => 'active',
        ]);
        setPermissionsTeamId($other->id);
        $user->givePermissionTo('patrols.manage');
        $this->asBearer($user->createToken('other-admin')->plainTextToken);
        $this->patchJson("/api/v1/admin/jwm-devices/{$id}", ['is_active' => false])->assertNotFound();
        $this->getJson('/api/v1/admin/jwm-devices')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_admin_can_map_exact_guard_and_checkpoint_ids_without_rebinding_them(): void
    {
        $id = $this->registerDevice();
        $session = $this->patrolSession();
        $checkpoint = $session->route->checkpoints()->firstOrFail();
        foreach (['0007', '7', 'AbC', 'abc'] as $externalId) {
            $this->postJson("/api/v1/admin/jwm-devices/{$id}/guard-mappings", [
                'external_id' => $externalId, 'guard_id' => $this->guard->id,
            ])->assertCreated()->assertJsonPath('data.external_id', $externalId)
                ->assertJsonMissingPath('data.external_id_hash');
        }
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/checkpoint-mappings", [
            'external_id' => '00A1', 'patrol_checkpoint_id' => $checkpoint->id,
        ])->assertCreated()->assertJsonPath('data.patrol_checkpoint_id', $checkpoint->id);
        $this->getJson("/api/v1/admin/jwm-devices/{$id}/guard-mappings")
            ->assertOk()->assertJsonCount(4, 'data');
        $this->getJson("/api/v1/admin/jwm-devices/{$id}/checkpoint-mappings")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonMissingPath('data.0.external_id_hash');
        $otherGuard = Guard::where('id', '!=', $this->guard->id)->firstOrFail();
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/guard-mappings", [
            'external_id' => '0007', 'guard_id' => $otherGuard->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('external_id');
        $checkpoint->delete();
        $this->getJson("/api/v1/admin/jwm-devices/{$id}/checkpoint-mappings")
            ->assertOk()->assertJsonPath('data.0.patrol_checkpoint_id', null);
        $newCheckpoint = PatrolCheckpoint::create([
            'tenant_id' => $this->admin->tenant_id, 'patrol_route_id' => $session->patrol_route_id,
            'name' => 'Replacement checkpoint', 'code' => 'JWM-CP-NEW', 'sequence' => 1,
        ]);
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/checkpoint-mappings", [
            'external_id' => '00A1', 'patrol_checkpoint_id' => $newCheckpoint->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('external_id');
        $otherId = $this->registerDevice('ANOTHER-MAPPING-READER');
        $this->postJson("/api/v1/admin/jwm-devices/{$otherId}/guard-mappings", [
            'external_id' => '0007', 'guard_id' => $otherGuard->id,
        ])->assertCreated();
        $this->patchJson("/api/v1/admin/jwm-devices/{$otherId}", ['is_active' => false])->assertOk();
        $this->postJson("/api/v1/admin/jwm-devices/{$otherId}/guard-mappings", [
            'external_id' => 'disabled-reader', 'guard_id' => $otherGuard->id,
        ])->assertUnprocessable();
    }

    public function test_mapping_management_rejects_foreign_targets_and_unprivileged_or_bridge_callers(): void
    {
        $id = $this->registerDevice();
        $session = $this->patrolSession();
        $checkpoint = $session->route->checkpoints()->firstOrFail();
        $foreign = Tenant::create(['name' => 'Foreign', 'slug' => 'jwm-map-foreign', 'status' => 'active']);
        $foreignGuard = Guard::create([
            'tenant_id' => $foreign->id, 'first_name' => 'Foreign', 'last_name' => 'Guard',
            'employee_number' => 'JWM-FOREIGN-MAP', 'status' => 'active',
        ]);
        PatrolCheckpoint::whereKey($checkpoint->id)->update(['tenant_id' => $foreign->id]);
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/guard-mappings", [
            'external_id' => 'g-1', 'guard_id' => $foreignGuard->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('guard_id');
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/checkpoint-mappings", [
            'external_id' => 'cp-1', 'patrol_checkpoint_id' => $checkpoint->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('patrol_checkpoint_id');
        foreach (['', "bad\nid", 'two words', ' padded', "\u{e9}", str_repeat('a', 101), 7] as $invalid) {
            $this->postJson("/api/v1/admin/jwm-devices/{$id}/guard-mappings", [
                'external_id' => $invalid, 'guard_id' => $this->guard->id,
            ])->assertUnprocessable()->assertJsonValidationErrors('external_id');
        }
        $bridge = $this->postJson("/api/v1/admin/jwm-devices/{$id}/bridge-token")
            ->assertCreated()->json('data.token');
        $this->asBearer($bridge);
        foreach (['guard-mappings', 'checkpoint-mappings'] as $path) {
            $this->getJson("/api/v1/admin/jwm-devices/{$id}/{$path}")->assertForbidden();
            $this->postJson("/api/v1/admin/jwm-devices/{$id}/{$path}", [])->assertForbidden();
        }
        $guardUser = User::where('email', 'john.guard@test')->firstOrFail();
        $this->asBearer($guardUser->createToken('guard-map-test')->plainTextToken);
        $this->getJson("/api/v1/admin/jwm-devices/{$id}/guard-mappings")->assertForbidden();
        $this->asBearer($this->adminToken);
        JwmDevice::whereKey($id)->update(['tenant_id' => $foreign->id]);
        foreach (['guard-mappings', 'checkpoint-mappings'] as $path) {
            $this->getJson("/api/v1/admin/jwm-devices/{$id}/{$path}")->assertNotFound();
        }
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/guard-mappings", [
            'external_id' => 'g-1', 'guard_id' => $this->guard->id,
        ])->assertNotFound();
        $this->assertDatabaseCount('jwm_guard_mappings', 0);
        $this->assertDatabaseCount('jwm_checkpoint_mappings', 0);
    }

    public function test_bridge_token_is_restricted_to_ingestion_and_rotation_revokes_previous_token(): void
    {
        $id = $this->registerDevice();
        $first = $this->postJson("/api/v1/admin/jwm-devices/{$id}/bridge-token")
            ->assertCreated()->json('data.token');
        $second = $this->postJson("/api/v1/admin/jwm-devices/{$id}/bridge-token")
            ->assertCreated()->json('data.token');

        $this->asBearer($first);
        $this->postJson("/api/v1/jwm/devices/{$id}/events", [])->assertUnauthorized();
        $this->asBearer($second);
        $this->getJson('/api/v1/admin/guards')->assertForbidden();
        $this->getJson('/api/enterprise/analytics')->assertForbidden();
        $this->getJson('/api/v1/admin/jwm-devices')->assertForbidden();
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/bridge-token")->assertForbidden();
    }

    public function test_gps_uses_the_guard_at_occurrence_time_and_replays_without_duplicate_locations(): void
    {
        $id = $this->readyDevice();
        $original = $this->guard->id;
        $replacement = Guard::create([
            'tenant_id' => $this->admin->tenant_id, 'first_name' => 'Next', 'last_name' => 'Guard',
            'employee_number' => 'JWM-NEXT', 'status' => 'active',
        ]);
        JwmDeviceAssignment::where('jwm_device_id', $id)->update(['ends_at' => now()->subMinutes(30)]);
        JwmDeviceAssignment::create([
            'tenant_id' => $this->admin->tenant_id, 'jwm_device_id' => $id,
            'guard_id' => $replacement->id, 'starts_at' => now()->subMinutes(30), 'ends_at' => now()->addHour(),
        ]);
        $payload = $this->gpsPayload();
        $response = $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertCreated()->assertJsonPath('data.guard_id', $original)->assertJsonPath('replayed', false);
        $locationId = $response->json('data.guard_location_id');
        $location = GuardLocation::findOrFail($locationId);
        $this->assertSame('jwm', $location->source);
        $this->assertTrue($location->recorded_at->equalTo(now()->subHour()));
        $this->assertNull($location->accuracy_meters);

        $payload['occurred_at'] = now()->subHour()->setTimezone('+02:00')->toIso8601String();
        $payload['latitude'] = '5.6037';
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertOk()->assertJsonPath('replayed', true)->assertJsonPath('data.guard_location_id', $locationId);
        $this->assertDatabaseCount('jwm_device_events', 1);
        $this->assertSame(1, GuardLocation::where('source', 'jwm')->count());

        $payload['longitude'] = -1;
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)->assertConflict();
        $this->assertDatabaseCount('jwm_device_events', 1);
    }

    public function test_guard_handover_boundary_is_start_inclusive_and_end_exclusive(): void
    {
        $id = $this->readyDevice();
        $interval = JwmDeviceAssignment::where('jwm_device_id', $id)->firstOrFail();
        $payload = $this->gpsPayload();
        $payload['occurred_at'] = $interval->starts_at->toIso8601String();
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)->assertCreated();
        $interval->update(['ends_at' => now()->subHour()]);
        $payload['event_id'] = 'boundary-end';
        $payload['occurred_at'] = now()->subHour()->toIso8601String();
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('occurred_at');
        $this->assertDatabaseCount('jwm_device_events', 1);
    }

    public function test_invalid_or_unattributable_events_leave_no_partial_writes(): void
    {
        $id = $this->readyDevice();
        $base = $this->gpsPayload();
        foreach ([
            ['latitude' => 91], ['longitude' => -181], ['longitude' => null],
            ['occurred_at' => now()->addMinute()->toIso8601String()],
            ['occurred_at' => now()->subHour()->format('Y-m-d H:i:s')],
            ['occurred_at' => '2026-02-30T12:00:00Z'],
            ['occurred_at' => '2026-01-01T12:00:00+14:30'],
            ['guard_id' => $this->guard->id], ['tenant_id' => $this->admin->tenant_id],
            ['type' => 'alarm'], ['event_id' => 'UPPERCASE'],
        ] as $invalid) {
            $this->postJson("/api/v1/jwm/devices/{$id}/events", array_replace($base, $invalid))
                ->assertUnprocessable();
        }
        JwmDeviceAssignment::create([
            'tenant_id' => $this->admin->tenant_id, 'jwm_device_id' => $id,
            'guard_id' => $this->guard->id, 'starts_at' => now()->subHours(2), 'ends_at' => now()->addHour(),
        ]);
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $this->gpsPayload())
            ->assertUnprocessable()->assertJsonValidationErrors('occurred_at');
        JwmDeviceAssignment::where('jwm_device_id', $id)->delete();
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $base)
            ->assertUnprocessable()->assertJsonValidationErrors('occurred_at');
        $this->assertDatabaseCount('jwm_device_events', 0);
        $this->assertSame(0, GuardLocation::where('source', 'jwm')->count());
    }

    public function test_ingestion_rejects_admin_tokens_other_devices_and_disabled_devices(): void
    {
        $id = $this->readyDevice();
        $bridge = $this->defaultHeaders['Authorization'];
        $this->asBearer($this->adminToken);
        $otherId = $this->registerDevice('TEST-READER-02');
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $this->gpsPayload())->assertForbidden();
        $this->asBearer(substr($bridge, strlen('Bearer ')));
        $this->postJson("/api/v1/jwm/devices/{$otherId}/events", $this->gpsPayload())->assertForbidden();
        JwmDevice::whereKey($id)->update(['is_active' => false]);
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $this->gpsPayload())->assertForbidden();
        $this->assertDatabaseCount('jwm_device_events', 0);
    }

    public function test_cross_tenant_bridge_cannot_submit_to_or_attribute_records_in_another_tenant(): void
    {
        $session = $this->patrolSession();
        $id = $this->readyDevice();
        $device = JwmDevice::findOrFail($id);
        $foreign = Tenant::create(['name' => 'Foreign', 'slug' => 'jwm-foreign', 'status' => 'active']);
        JwmDevice::whereKey($id)->update(['tenant_id' => $foreign->id]);
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $this->gpsPayload())->assertNotFound();
        JwmDevice::withoutGlobalScopes()->whereKey($id)->update(['tenant_id' => $device->tenant_id]);
        PatrolSession::whereKey($session->id)->update(['tenant_id' => $foreign->id]);
        $payload = [
            'event_id' => 'foreign-session', 'type' => 'checkpoint',
            'occurred_at' => now()->subHour()->toIso8601String(),
            'patrol_session_id' => $session->id, 'checkpoint_code' => 'JWM-CP-01',
        ];
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)->assertUnprocessable();
        $this->assertDatabaseCount('jwm_device_events', 0);
    }

    public function test_disabled_or_expired_bridge_credentials_and_inactive_issuer_cannot_ingest(): void
    {
        $id = $this->readyDevice();
        $token = PersonalAccessToken::findOrFail(JwmDevice::findOrFail($id)->bridge_token_id);
        $token->update(['expires_at' => now()->subSecond()]);
        $this->asBearer(substr($this->defaultHeaders['Authorization'], strlen('Bearer ')));
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $this->gpsPayload())->assertUnauthorized();
        $token->update(['expires_at' => now()->addHour()]);
        $this->admin->update(['status' => 'inactive']);
        $this->asBearer(substr($this->defaultHeaders['Authorization'], strlen('Bearer ')));
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $this->gpsPayload())->assertForbidden();
        $this->admin->update(['status' => 'active']);
        $this->asBearer($this->adminToken);
        $this->patchJson("/api/v1/admin/jwm-devices/{$id}", ['is_active' => false])->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->id]);
        $this->assertDatabaseCount('jwm_device_events', 0);
    }

    public function test_location_retention_does_not_remove_replay_protection(): void
    {
        $id = $this->readyDevice();
        $payload = $this->gpsPayload();
        $locationId = $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertCreated()->json('data.guard_location_id');
        GuardLocation::findOrFail($locationId)->delete();
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertOk()->assertJsonPath('replayed', true)->assertJsonPath('data.guard_location_id', null);
        $this->assertSame(0, GuardLocation::where('source', 'jwm')->count());
    }

    public function test_out_of_order_or_wrong_guard_checkpoint_uploads_are_rejected(): void
    {
        $session = $this->patrolSession();
        $checkpoint = $session->route->checkpoints()->firstOrFail();
        $laterCheckpoint = PatrolCheckpoint::create([
            'tenant_id' => $this->admin->tenant_id, 'patrol_route_id' => $session->patrol_route_id,
            'name' => 'Second checkpoint', 'code' => 'JWM-CP-02', 'sequence' => 2,
        ]);
        $id = $this->readyDevice();
        $payload = [
            'event_id' => 'cp-order-1', 'type' => 'checkpoint',
            'occurred_at' => now()->subMinutes(30)->toIso8601String(),
            'patrol_session_id' => $session->id, 'checkpoint_code' => $checkpoint->code,
        ];
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)->assertCreated();
        $payload['event_id'] = 'cp-order-2';
        $payload['occurred_at'] = now()->subHour()->toIso8601String();
        $payload['checkpoint_code'] = $laterCheckpoint->code;
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('occurred_at');
        $session->update(['guard_id' => Guard::where('id', '!=', $this->guard->id)->firstOrFail()->id]);
        $payload['occurred_at'] = now()->toIso8601String();
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('patrol_session_id');
        $this->assertSame(1, $session->scans()->count());
        $this->assertDatabaseCount('jwm_device_events', 1);
    }

    public function test_checkpoint_preserves_original_time_accepts_no_gps_and_replays_after_completion(): void
    {
        $session = $this->patrolSession();
        $checkpoint = $session->route->checkpoints()->firstOrFail();
        $id = $this->readyDevice();
        $payload = [
            'event_id' => 'checkpoint-001', 'type' => 'checkpoint',
            'occurred_at' => now()->subHour()->toIso8601String(),
            'patrol_session_id' => $session->id, 'checkpoint_code' => $checkpoint->code,
        ];
        $response = $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)->assertCreated();
        $scanId = $response->json('data.checkpoint_scan_id');
        $scan = $session->scans()->findOrFail($scanId);
        $this->assertTrue($scan->scanned_at->equalTo(now()->subHour()));
        $this->assertNull($scan->latitude);
        $this->assertNull($scan->longitude);
        $this->assertSame('completed', $session->fresh()->status);
        $this->assertSame(100, (int) $session->fresh()->completion_percent);
        JwmDevice::whereKey($id)->update(['require_device_identity' => true]);
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertOk()->assertJsonPath('data.checkpoint_scan_id', $scanId);
        $this->assertSame(1, $session->scans()->count());
        $this->assertDatabaseCount('jwm_device_events', 1);
    }

    public function test_identified_checkpoint_uses_exact_mappings_and_replays_before_mutable_checks(): void
    {
        $session = $this->patrolSession();
        $id = $this->readyIdentifiedDevice($session);
        $payload = [
            'event_id' => 'identified-checkpoint', 'type' => 'checkpoint',
            'occurred_at' => now()->subHour()->toIso8601String(), 'patrol_session_id' => $session->id,
            'guard_external_id' => '0007', 'checkpoint_external_id' => '00A1',
        ];
        $response = $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertCreated()->assertJsonPath('data.guard_id', $this->guard->id)
            ->assertJsonPath('data.guard_external_id', '0007')->assertJsonPath('data.checkpoint_external_id', '00A1');
        $scanId = $response->json('data.checkpoint_scan_id');
        $checkpoint = $session->route->checkpoints()->firstOrFail();
        $this->assertSame($checkpoint->id, $session->scans()->findOrFail($scanId)->patrol_checkpoint_id);
        $this->assertSame('completed', $session->fresh()->status);
        DB::table('jwm_guard_mappings')->where('jwm_device_id', $id)->update(['guard_id' => null]);
        $checkpoint->delete();
        JwmDeviceAssignment::where('jwm_device_id', $id)->delete();
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertOk()->assertJsonPath('replayed', true);
        $payload['guard_external_id'] = '7';
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)->assertConflict();
        $payload['guard_external_id'] = '0007';
        $payload['event_id'] = 'new-event-with-deleted-mapping';
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)->assertUnprocessable();
        $this->assertDatabaseCount('jwm_device_events', 1);
    }

    public function test_strict_checkpoint_identity_cannot_fall_back_to_handover_or_internal_code(): void
    {
        $session = $this->patrolSession();
        $id = $this->readyIdentifiedDevice($session);
        $bridge = substr($this->defaultHeaders['Authorization'], strlen('Bearer '));
        $this->asBearer($this->adminToken);
        $otherGuard = Guard::where('id', '!=', $this->guard->id)->firstOrFail();
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/guard-mappings", [
            'external_id' => 'conflicting-guard', 'guard_id' => $otherGuard->id,
        ])->assertCreated();
        $otherDevice = $this->registerDevice('ONLY-OTHER-MAPPINGS');
        $this->postJson("/api/v1/admin/jwm-devices/{$otherDevice}/guard-mappings", [
            'external_id' => 'only-other-reader', 'guard_id' => $this->guard->id,
        ])->assertCreated();
        $this->postJson("/api/v1/admin/jwm-devices/{$otherDevice}/checkpoint-mappings", [
            'external_id' => 'only-other-checkpoint', 'patrol_checkpoint_id' => $session->route->checkpoints()->firstOrFail()->id,
        ])->assertCreated();
        $this->asBearer($bridge);
        $base = [
            'event_id' => 'invalid-identified-checkpoint', 'type' => 'checkpoint',
            'occurred_at' => now()->subHour()->toIso8601String(), 'patrol_session_id' => $session->id,
            'guard_external_id' => '0007', 'checkpoint_external_id' => '00A1',
        ];
        foreach ([
            ['guard_external_id' => null], ['guard_external_id' => 'missing'],
            ['guard_external_id' => '7'], ['guard_external_id' => 'conflicting-guard'],
            ['guard_external_id' => 'only-other-reader'],
            ['guard_external_id' => 7], ['guard_external_id' => 'bad id'], ['guard_external_id' => '0007 '],
            ['checkpoint_external_id' => null, 'checkpoint_code' => 'JWM-CP-01'],
            ['checkpoint_external_id' => 'missing'], ['checkpoint_external_id' => '00a1'],
            ['checkpoint_external_id' => 'only-other-checkpoint'],
            ['checkpoint_code' => 'JWM-CP-01'],
        ] as $invalid) {
            $this->postJson("/api/v1/jwm/devices/{$id}/events", array_replace($base, $invalid))
                ->assertUnprocessable();
        }
        unset($base['guard_external_id']);
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $base)
            ->assertUnprocessable()->assertJsonValidationErrors('guard_external_id');
        $this->assertDatabaseCount('jwm_device_events', 0);
        $this->assertSame(0, $session->scans()->count());
        $this->assertSame('in_progress', $session->fresh()->status);
    }

    public function test_mapping_targets_must_still_belong_to_the_tenant_and_patrol_route(): void
    {
        $session = $this->patrolSession();
        $id = $this->readyIdentifiedDevice($session);
        $payload = [
            'event_id' => 'mapping-scope', 'type' => 'checkpoint',
            'occurred_at' => now()->subHour()->toIso8601String(), 'patrol_session_id' => $session->id,
            'guard_external_id' => '0007', 'checkpoint_external_id' => '00A1',
        ];
        $foreign = Tenant::create(['name' => 'Foreign', 'slug' => 'jwm-event-map-foreign', 'status' => 'active']);
        DB::table('jwm_guard_mappings')->where('jwm_device_id', $id)->update(['tenant_id' => $foreign->id]);
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('guard_external_id');
        DB::table('jwm_guard_mappings')->where('jwm_device_id', $id)->update(['tenant_id' => $this->admin->tenant_id]);
        $this->guard->update(['tenant_id' => $foreign->id]);
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('guard_external_id');
        $this->guard->update(['tenant_id' => $this->admin->tenant_id]);
        $otherRoute = $session->route->replicate();
        $otherRoute->save();
        $checkpoint = $session->route->checkpoints()->firstOrFail();
        $checkpoint->update(['patrol_route_id' => $otherRoute->id]);
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('checkpoint_external_id');
        $checkpoint->update(['patrol_route_id' => $session->patrol_route_id, 'tenant_id' => $foreign->id]);
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('checkpoint_external_id');
        $this->assertDatabaseCount('jwm_device_events', 0);
        $this->assertSame(0, $session->scans()->count());
    }

    public function test_gps_identity_is_optional_but_supplied_identity_is_never_ignored(): void
    {
        $session = $this->patrolSession();
        $id = $this->readyIdentifiedDevice($session);
        $payload = $this->gpsPayload();
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)->assertCreated();
        $payload['event_id'] = 'gps-with-id';
        $payload['guard_external_id'] = '0007';
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertCreated()->assertJsonPath('data.guard_id', $this->guard->id);
        $payload['event_id'] = 'gps-unknown-id';
        $payload['guard_external_id'] = 'unknown';
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('guard_external_id');
        JwmDevice::whereKey($id)->update(['require_device_identity' => false]);
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('guard_external_id');
        $this->assertDatabaseCount('jwm_device_events', 2);
        $this->assertSame(2, GuardLocation::where('source', 'jwm')->count());
    }

    public function test_bad_checkpoint_context_and_sequence_roll_back_the_ledger(): void
    {
        $session = $this->patrolSession();
        $checkpoint = $session->route->checkpoints()->firstOrFail();
        $id = $this->readyDevice();
        $base = [
            'event_id' => 'checkpoint-rejected', 'type' => 'checkpoint',
            'occurred_at' => now()->subHour()->toIso8601String(),
            'patrol_session_id' => $session->id, 'checkpoint_code' => $checkpoint->code,
        ];
        foreach ([
            ['checkpoint_code' => 'MISSING-CHECKPOINT'],
            ['patrol_session_id' => 999999],
            ['occurred_at' => now()->subMinutes(119)->toIso8601String()],
            ['latitude' => 5],
        ] as $invalid) {
            $this->postJson("/api/v1/jwm/devices/{$id}/events", array_replace($base, $invalid))
                ->assertUnprocessable();
        }
        $checkpoint->update(['sequence' => 2]);
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $base)
            ->assertUnprocessable()->assertJsonValidationErrors('checkpoint_code');
        $session->update(['status' => 'completed', 'completed_at' => now()]);
        $this->postJson("/api/v1/jwm/devices/{$id}/events", $base)->assertUnprocessable();
        $this->assertDatabaseCount('jwm_device_events', 0);
        $this->assertSame(0, $session->scans()->count());
        $this->assertSame(0, (int) $session->fresh()->completion_percent);
    }

    private function readyDevice(): int
    {
        $id = $this->registerDevice();
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/assignments", [
            'guard_id' => $this->guard->id,
            'starts_at' => now()->subHours(2)->toIso8601String(),
            'ends_at' => now()->addHour()->toIso8601String(),
        ])->assertCreated();
        $token = $this->postJson("/api/v1/admin/jwm-devices/{$id}/bridge-token")
            ->assertCreated()->json('data.token');
        $this->asBearer($token);

        return $id;
    }

    private function readyIdentifiedDevice(PatrolSession $session): int
    {
        $id = $this->readyDevice();
        $bridge = substr($this->defaultHeaders['Authorization'], strlen('Bearer '));
        $this->asBearer($this->adminToken);
        $this->patchJson("/api/v1/admin/jwm-devices/{$id}", ['require_device_identity' => true])
            ->assertOk()->assertJsonPath('data.require_device_identity', true)->assertJsonPath('data.is_active', true);
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/guard-mappings", [
            'external_id' => '0007', 'guard_id' => $this->guard->id,
        ])->assertCreated();
        $this->postJson("/api/v1/admin/jwm-devices/{$id}/checkpoint-mappings", [
            'external_id' => '00A1', 'patrol_checkpoint_id' => $session->route->checkpoints()->firstOrFail()->id,
        ])->assertCreated();
        $this->asBearer($bridge);

        return $id;
    }

    private function gpsPayload(): array
    {
        return [
            'event_id' => 'gps-001', 'type' => 'gps',
            'occurred_at' => now()->subHour()->toIso8601String(),
            'latitude' => 5.6037, 'longitude' => -0.187,
        ];
    }

    private function patrolSession(): PatrolSession
    {
        $assignment = ShiftAssignment::where('guard_id', $this->guard->id)->firstOrFail();
        $route = PatrolRoute::create([
            'tenant_id' => $this->admin->tenant_id, 'site_id' => $assignment->shift->site_id,
            'name' => 'JWM test route', 'status' => 'active',
        ]);
        PatrolCheckpoint::create([
            'tenant_id' => $this->admin->tenant_id, 'patrol_route_id' => $route->id,
            'name' => 'JWM checkpoint', 'code' => 'JWM-CP-01', 'sequence' => 1,
        ]);

        return PatrolSession::create([
            'tenant_id' => $this->admin->tenant_id, 'patrol_route_id' => $route->id,
            'shift_assignment_id' => $assignment->id, 'guard_id' => $this->guard->id,
            'status' => 'in_progress', 'started_at' => now()->subMinutes(90), 'completion_percent' => 0,
        ]);
    }

    private function registerDevice(string $reader = 'TEST-READER-01'): int
    {
        return $this->postJson('/api/v1/admin/jwm-devices', [
            'name' => 'Shared JWM', 'reader_number' => $reader,
        ])->assertCreated()->assertJsonPath('data.model', 'JWM5000ZG')
            ->assertJsonPath('data.is_active', true)->json('data.id');
    }

    private function asBearer(string $token): void
    {
        $this->app['auth']->forgetGuards();
        $this->withToken($token);
    }
}
