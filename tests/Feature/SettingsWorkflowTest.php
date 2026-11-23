<?php

namespace Tests\Feature;

use App\Jobs\ProcessOfflineSyncBatch;
use App\Livewire\Mobile\OfflineSyncMonitor;
use App\Livewire\Settings\IdCardSettings;
use App\Livewire\Settings\NotificationTemplateIndex;
use App\Livewire\Settings\RolePermissionManager;
use App\Livewire\Settings\SettingsHub;
use App\Livewire\Settings\StaffUserIndex;
use App\Livewire\Settings\TeamPasswordReset;
use App\Livewire\Settings\ThemeSettings;
use App\Livewire\Settings\TwoFactorSetup;
use App\Livewire\Settings\WebhookManager;
use App\Models\ClientAccount;
use App\Models\NotificationTemplate;
use App\Models\OfflineSyncBatch;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantBrandingService;
use App\Services\TenantRoleProvisioner;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SettingsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@demo.test')->firstOrFail();
        app()->instance('currentTenant', $this->admin->tenant);
        setPermissionsTeamId($this->admin->tenant_id);
    }

    public function test_team_password_reset_lists_only_staff_accounts(): void
    {
        $guardUser = User::where('email', 'john.guard@test')->firstOrFail();
        $clientUser = User::create([
            'tenant_id' => $this->admin->tenant_id,
            'client_account_id' => ClientAccount::where('tenant_id', $this->admin->tenant_id)->value('id'),
            'name' => 'Portal Viewer',
            'email' => 'portal-settings@test.example',
            'password' => 'password',
            'status' => 'active',
        ]);

        Livewire::actingAs($this->admin)
            ->test(TeamPasswordReset::class)
            ->assertDontSee($guardUser->email)
            ->assertDontSee($clientUser->email);
    }

    public function test_staff_cannot_deactivate_self_through_edit_form(): void
    {
        Livewire::actingAs($this->admin)
            ->test(StaffUserIndex::class)
            ->call('edit', $this->admin->id)
            ->set('form.status', 'inactive')
            ->call('save')
            ->assertHasErrors('form.status');

        $this->assertSame('active', $this->admin->fresh()->status);
    }

    public function test_last_active_company_admin_cannot_remove_own_admin_role(): void
    {
        Livewire::actingAs($this->admin)
            ->test(StaffUserIndex::class)
            ->call('edit', $this->admin->id)
            ->set('form.role', 'operations-manager')
            ->call('save')
            ->assertHasErrors('form.role');

        $this->assertTrue($this->admin->fresh()->hasRole('company-admin'));
    }

    public function test_custom_role_permissions_survive_staff_role_sync(): void
    {
        $role = Role::where('tenant_id', $this->admin->tenant_id)
            ->where('name', 'operations-manager')
            ->firstOrFail();
        $original = $role->permissions->pluck('name')->all();
        $custom = array_values(array_diff($original, ['attendance.manage']));
        $role->syncPermissions($custom);

        app(TenantRoleProvisioner::class)->provision($this->admin->tenant);

        $this->assertEqualsCanonicalizing($custom, $role->fresh()->permissions->pluck('name')->all());
    }

    public function test_role_names_are_validated_and_normalized(): void
    {
        Livewire::actingAs($this->admin)
            ->test(RolePermissionManager::class)
            ->set('roleName', ' ')
            ->call('createRole')
            ->assertHasErrors('roleName');

        Livewire::actingAs($this->admin)
            ->test(RolePermissionManager::class)
            ->set('roleName', 'Night Supervisor')
            ->call('createRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'night-supervisor',
        ]);
    }

    public function test_webhooks_reject_private_network_destinations(): void
    {
        Livewire::actingAs($this->admin)
            ->test(WebhookManager::class)
            ->call('openForm')
            ->set('event', 'incident.submitted')
            ->set('targetUrl', 'http://127.0.0.1:8080/hook')
            ->call('create')
            ->assertHasErrors('targetUrl');
    }

    public function test_duplicate_notification_template_has_a_field_error(): void
    {
        NotificationTemplate::create([
            'tenant_id' => $this->admin->tenant_id,
            'code' => 'incident.submitted',
            'channel' => 'mail',
            'subject' => 'Incident',
            'body' => 'Submitted',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(NotificationTemplateIndex::class)
            ->call('openForm')
            ->set('form.code', 'incident.submitted')
            ->set('form.channel', 'mail')
            ->set('form.body', 'Duplicate')
            ->call('save')
            ->assertHasErrors('form.code');
    }

    public function test_offline_batch_can_only_be_queued_once(): void
    {
        Queue::fake();
        $batch = OfflineSyncBatch::create([
            'tenant_id' => $this->admin->tenant_id,
            'user_id' => $this->admin->id,
            'payload' => [['type' => 'location', 'latitude' => 5, 'longitude' => -1]],
            'status' => 'pending',
        ]);

        $component = Livewire::actingAs($this->admin)->test(OfflineSyncMonitor::class);
        $component->call('process', $batch->id)
            ->call('process', $batch->id)
            ->assertHasErrors('batch');

        $this->assertSame('processing', $batch->fresh()->status);
    }

    public function test_mobile_offline_sync_queues_a_processable_batch(): void
    {
        Queue::fake();
        $guardUser = User::where('email', 'john.guard@test')->firstOrFail();
        Sanctum::actingAs($guardUser);

        $response = $this->postJson('/api/v1/offline-sync', [
            'payload' => [[
                'type' => 'location',
                'latitude' => 5.6037,
                'longitude' => -0.1870,
            ]],
        ]);

        $response->assertOk()->assertJson(['status' => 'queued']);
        $batch = OfflineSyncBatch::whereKey($response->json('batch_id'))->firstOrFail();
        $this->assertSame('processing', $batch->status);
        Queue::assertPushed(ProcessOfflineSyncBatch::class, fn ($job) => $job->batch->is($batch));
    }

    public function test_2fa_verification_is_rate_limited(): void
    {
        $service = app(TwoFactorService::class);
        $secret = $service->generateSecret();
        $this->admin->update(['two_factor_secret' => $secret, 'two_factor_confirmed_at' => null]);
        $this->actingAs($this->admin->fresh());

        RateLimiter::clear('two-factor:'.$this->admin->id);
        $component = new TwoFactorSetup;
        $component->code = '000000';

        foreach (range(1, 6) as $attempt) {
            try {
                $component->enable($service);
            } catch (HttpException) {
                // Invalid codes share the same rate-limit key.
            }
        }

        $this->assertGreaterThanOrEqual(5, RateLimiter::attempts('two-factor:'.$this->admin->id));
    }

    public function test_id_card_controls_have_accessible_names(): void
    {
        Livewire::actingAs($this->admin)
            ->test(IdCardSettings::class)
            ->assertSeeHtml('aria-label="Primary color picker"')
            ->assertSeeHtml('aria-label="Primary color value"')
            ->assertSeeHtml('aria-label="Upload front logo"')
            ->assertSeeHtml('aria-label="Upload back logo"')
            ->assertSeeHtml('aria-label="Upload authorized signature"');
    }

    public function test_existing_two_factor_secrets_are_encrypted_without_double_encrypting(): void
    {
        DB::table('users')->where('id', $this->admin->id)->update([
            'two_factor_secret' => 'JBSWY3DPEHPK3PXP',
        ]);

        $migration = require database_path('migrations/2026_11_19_000006_encrypt_two_factor_secrets.php');
        $migration->up();
        $migration->up();

        $stored = DB::table('users')->where('id', $this->admin->id)->value('two_factor_secret');
        $this->assertNotSame('JBSWY3DPEHPK3PXP', $stored);
        $this->assertSame('JBSWY3DPEHPK3PXP', Crypt::decryptString($stored));
        $this->assertSame('JBSWY3DPEHPK3PXP', $this->admin->fresh()->two_factor_secret);
    }

    public function test_tenant_currency_can_be_changed_from_settings(): void
    {
        Livewire::actingAs($this->admin)
            ->test(SettingsHub::class)
            ->set('currency', 'USD')
            ->call('saveCurrency')
            ->assertHasNoErrors();

        $this->assertSame('USD', $this->admin->tenant->fresh()->currency);
        $this->assertSame('$12.50', money(12.5));
    }

    public function test_organization_theme_is_persisted_and_applied_only_to_its_tenant(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ThemeSettings::class)
            ->set('lightColor', '#8430BF')
            ->set('darkColor', '#008376')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('settings.themes'));

        $this->get(route('settings.themes'))
            ->assertOk()
            ->assertSee('--tenant-accent: #8430bf;')
            ->assertSee('--tenant-dark-accent: #008376;');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['<body', '--tenant-accent: #8430bf;'], escape: false);

        $this->get(route('guards.apply', ['tenant' => $this->admin->tenant->slug]))
            ->assertOk()
            ->assertSeeInOrder(['<body', '--tenant-accent: #8430bf;'], escape: false);

        $this->get(route('guard.verify', ['tenant' => $this->admin->tenant->slug, 'token' => 'DEMOVERIFY01']))
            ->assertOk()
            ->assertSeeInOrder(['<body', '--tenant-accent: #8430bf;'], escape: false);

        $otherTenant = Tenant::factory()->create();
        $this->assertDatabaseMissing('tenant_settings', [
            'tenant_id' => $otherTenant->id,
            'key' => 'organization_theme',
        ]);
        $branding = app(TenantBrandingService::class);
        $this->assertSame(['light' => null, 'dark' => null], $branding->forTenant($otherTenant)['theme']);
    }

    public function test_theme_settings_reject_invalid_colors_and_unauthorized_users(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ThemeSettings::class)
            ->set('lightColor', 'red')
            ->call('save')
            ->assertHasErrors(['lightColor' => 'regex']);

        $guard = User::where('email', 'john.guard@test')->firstOrFail();
        $this->actingAs($guard)->get(route('settings.themes'))->assertForbidden();
    }
}
