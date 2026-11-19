<?php

namespace Tests\Feature;

use App\Livewire\Patrols\JwmDeviceManager;
use App\Models\Guard;
use App\Models\JwmDevice;
use App\Models\PatrolCheckpoint;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class JwmDeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@demo.test')->firstOrFail();
        $this->actingAs($this->admin);
        app()->instance('currentTenant', $this->admin->tenant);
        setPermissionsTeamId($this->admin->tenant_id);
    }

    public function test_admin_can_register_a_reader_and_configure_it_without_creating_credentials(): void
    {
        $this->get('/patrols/devices')->assertOk()->assertSee('Patrol readers');
        $screen = Livewire::test(JwmDeviceManager::class)
            ->call('openCreate')
            ->set('form.name', 'North gate reader')->set('form.reader_number', 'UI-001')
            ->call('save')->assertHasNoErrors()->assertSet('showForm', false)
            ->assertSee('North gate reader')->assertSee('Not monitored')
            ->assertSee('Reader configuration saved. No command was sent to the device.');
        $device = JwmDevice::where('reader_number', 'UI-001')->firstOrFail();
        $token = $this->admin->createToken('existing-bridge', ['jwm:ingest']);
        $device->update(['bridge_token_id' => $token->accessToken->id]);
        $screen->call('openEditor', 'settings')
            ->set('form.is_active', false)->set('form.require_device_identity', true)
            ->call('save')->assertHasNoErrors()->assertSee('Disabled')->assertSee('Strict identification');
        $this->assertFalse($device->fresh()->is_active);
        $this->assertTrue($device->fresh()->require_device_identity);
        $this->assertNull($device->fresh()->bridge_token_id);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $token->accessToken->id]);
    }

    public function test_admin_can_record_handovers_and_exact_immutable_mappings_from_the_drawer(): void
    {
        $guard = Guard::where('tenant_id', $this->admin->tenant_id)->firstOrFail();
        $checkpoint = PatrolCheckpoint::where('tenant_id', $this->admin->tenant_id)->firstOrFail();
        $device = $this->device();
        $screen = Livewire::test(JwmDeviceManager::class)
            ->call('selectDevice', $device->id)->call('openEditor', 'handover')
            ->set('form.guard_id', (string) $guard->id)
            ->set('form.starts_at', '2026-11-16T08:00:00Z')->set('form.ends_at', '2026-11-16T16:00:00Z')
            ->call('save')->assertHasNoErrors()->assertSee($guard->first_name)
            ->call('openEditor', 'handover')
            ->set('form.guard_id', (string) $guard->id)
            ->set('form.starts_at', '2026-11-16T09:00:00Z')->set('form.ends_at', '2026-11-16T15:00:00Z')
            ->call('save')->assertHasErrors('form.starts_at')->assertSet('showForm', true)
            ->call('closeDrawer')->call('openEditor', 'guard')
            ->set('form.external_id', '0007')->set('form.guard_id', (string) $guard->id)
            ->call('save')->assertHasNoErrors()->assertSee('0007')
            ->call('openEditor', 'guard')
            ->set('form.external_id', '0007')->set('form.guard_id', (string) $guard->id)
            ->call('save')->assertHasErrors('form.external_id')
            ->call('closeDrawer')->call('openEditor', 'checkpoint')
            ->set('form.external_id', '00A1')->set('form.patrol_checkpoint_id', (string) $checkpoint->id)
            ->call('save')->assertHasNoErrors()->assertSee('00A1')->assertSee($checkpoint->name);
        $checkpoint->delete();
        $screen->call('$refresh')->assertSee('Deleted checkpoint; ID reserved');
        $this->assertDatabaseCount('jwm_device_assignments', 1);
        $this->assertDatabaseHas('jwm_guard_mappings', ['external_id' => '0007', 'guard_id' => $guard->id]);
        $this->assertDatabaseHas('jwm_checkpoint_mappings', ['external_id' => '00A1', 'patrol_checkpoint_id' => null]);
    }

    public function test_reader_selection_search_and_mapping_targets_are_tenant_scoped(): void
    {
        $own = $this->device();
        $foreign = Tenant::create(['name' => 'Other', 'slug' => 'other-ui-reader', 'status' => 'active']);
        $other = JwmDevice::create(['tenant_id' => $foreign->id, 'name' => 'Foreign secret reader', 'reader_number' => 'FOREIGN-UI']);
        $foreignGuard = Guard::create([
            'tenant_id' => $foreign->id, 'first_name' => 'Foreign', 'last_name' => 'Person',
            'employee_number' => 'FOREIGN-UI-GUARD', 'status' => 'active',
        ]);
        setPermissionsTeamId($this->admin->tenant_id);
        Livewire::test(JwmDeviceManager::class)
            ->assertSee('UI test reader')->assertDontSee('Foreign secret reader')
            ->call('selectDevice', $own->id)->call('openEditor', 'guard')
            ->set('targetSearch', 'Foreign')->assertDontSee('FOREIGN-UI-GUARD')
            ->set('form.external_id', '0007')->set('form.guard_id', (string) $foreignGuard->id)
            ->call('save')->assertHasErrors('form.guard_id');
        $this->get('/patrols/devices?reader='.$other->id)->assertNotFound();
        $this->assertDatabaseCount('jwm_guard_mappings', 0);
        $this->expectException(ModelNotFoundException::class);
        Livewire::test(JwmDeviceManager::class)->call('selectDevice', $other->id);
    }

    public function test_authorization_is_checked_again_after_the_screen_has_mounted(): void
    {
        $device = $this->device();
        $screen = Livewire::test(JwmDeviceManager::class)
            ->call('selectDevice', $device->id)->call('openEditor', 'settings')
            ->set('form.is_active', false);
        $guardUser = User::where('email', 'john.guard@test')->firstOrFail();
        $this->actingAs($guardUser);
        $screen->call('save')->assertForbidden();
        $this->assertTrue($device->fresh()->is_active);
        $this->get('/patrols/devices')->assertForbidden();
    }

    public function test_open_settings_cannot_accidentally_save_to_a_changed_reader_selection(): void
    {
        $first = $this->device();
        $second = JwmDevice::create([
            'tenant_id' => $this->admin->tenant_id, 'name' => 'Second reader', 'reader_number' => 'UI-SECOND',
        ]);
        Livewire::test(JwmDeviceManager::class)
            ->call('selectDevice', $first->id)->call('openEditor', 'settings')
            ->set('form.is_active', false)->set('deviceId', $second->id)
            ->call('save')->assertStatus(409);
        $this->assertTrue($first->fresh()->is_active);
        $this->assertTrue($second->fresh()->is_active);
    }

    public function test_missing_migrations_show_explicit_setup_notice_instead_of_querying_missing_tables(): void
    {
        Schema::drop('jwm_guard_mappings');
        Livewire::test(JwmDeviceManager::class)
            ->assertSee('Database setup required')->assertDontSee('wire:click="openCreate"', false)
            ->call('openCreate')->set('form.name', 'Blocked reader')->set('form.reader_number', 'BLOCKED')
            ->call('save')->assertStatus(503);
        $this->assertDatabaseCount('jwm_devices', 0);
    }

    private function device(): JwmDevice
    {
        return JwmDevice::create([
            'tenant_id' => $this->admin->tenant_id, 'name' => 'UI test reader', 'reader_number' => 'UI-TEST',
        ])->refresh();
    }
}
