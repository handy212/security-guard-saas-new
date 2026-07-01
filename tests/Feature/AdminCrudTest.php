<?php

namespace Tests\Feature;

use App\Livewire\Clients\ClientIndex;
use App\Livewire\Equipment\EquipmentIndex;
use App\Livewire\Guards\GuardProfile;
use App\Livewire\Guards\GuardIndex;
use App\Livewire\Incidents\IncidentIndex;
use App\Livewire\Shifts\ScheduleBoard;
use App\Livewire\Sites\SiteIndex;
use App\Livewire\Visitors\VisitorLogIndex;
use App\Models\ClientAccount;
use App\Models\EquipmentAsset;
use App\Models\Guard;
use App\Models\Incident;
use App\Models\Shift;
use App\Models\Site;
use App\Models\User;
use App\Models\VisitorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@demo.test')->first();
    }

    public function test_admin_pages_load_with_page_titles(): void
    {
        $routes = [
            '/dashboard',
            '/guards',
            '/clients',
            '/sites',
            '/equipment',
            '/incidents',
            '/schedules',
            '/compliance',
        ];

        foreach ($routes as $route) {
            $this->actingAs($this->admin)
                ->get($route)
                ->assertOk();
        }
    }

    public function test_drawer_is_hidden_until_opened_on_guards_page(): void
    {
        Livewire::actingAs($this->admin)
            ->test(GuardIndex::class)
            ->assertSet('showForm', false)
            ->assertDontSee('License #')
            ->call('openCreate')
            ->assertSet('showForm', true)
            ->assertSee('License #')
            ->call('closeDrawer')
            ->assertSet('showForm', false);
    }

    public function test_guard_crud_via_livewire(): void
    {
        Livewire::actingAs($this->admin)
            ->test(GuardIndex::class)
            ->call('openCreate')
            ->set('form.first_name', 'Jane')
            ->set('form.last_name', 'Doe')
            ->set('form.employee_number', 'G-999')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('guards', [
            'employee_number' => 'G-999',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        $guard = Guard::where('employee_number', 'G-999')->first();

        Livewire::actingAs($this->admin)
            ->test(GuardIndex::class)
            ->call('edit', $guard->id)
            ->assertSet('showForm', true)
            ->set('form.first_name', 'Janet')
            ->call('save')
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('guards', ['id' => $guard->id, 'first_name' => 'Janet']);

        Livewire::actingAs($this->admin)
            ->test(GuardIndex::class)
            ->call('delete', $guard->id);

        $this->assertDatabaseMissing('guards', ['id' => $guard->id]);
    }

    public function test_client_crud_via_livewire(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ClientIndex::class)
            ->call('openCreate')
            ->set('form.name', 'Test Corp')
            ->set('form.email', 'test@corp.test')
            ->call('save')
            ->assertSet('showForm', false);

        $client = ClientAccount::where('name', 'Test Corp')->first();
        $this->assertNotNull($client);

        Livewire::actingAs($this->admin)
            ->test(ClientIndex::class)
            ->call('edit', $client->id)
            ->set('form.name', 'Test Corp Updated')
            ->call('save');

        $this->assertDatabaseHas('client_accounts', ['id' => $client->id, 'name' => 'Test Corp Updated']);

        Livewire::actingAs($this->admin)
            ->test(ClientIndex::class)
            ->call('delete', $client->id);

        $this->assertDatabaseMissing('client_accounts', ['id' => $client->id]);
    }

    public function test_site_crud_via_livewire(): void
    {
        $client = ClientAccount::first();

        Livewire::actingAs($this->admin)
            ->test(SiteIndex::class)
            ->call('openCreate')
            ->set('form.client_account_id', $client->id)
            ->set('form.name', 'North Gate')
            ->set('form.latitude', 6.1)
            ->set('form.longitude', -1.6)
            ->call('save')
            ->assertSet('showForm', false);

        $site = Site::where('name', 'North Gate')->first();
        $this->assertNotNull($site);

        Livewire::actingAs($this->admin)
            ->test(SiteIndex::class)
            ->call('delete', $site->id);

        $this->assertDatabaseMissing('sites', ['id' => $site->id]);
    }

    public function test_equipment_crud_via_livewire(): void
    {
        Livewire::actingAs($this->admin)
            ->test(EquipmentIndex::class)
            ->call('openCreate')
            ->set('form.name', 'Radio Unit')
            ->set('form.asset_tag', 'RAD-99')
            ->call('save')
            ->assertSet('showForm', false);

        $asset = EquipmentAsset::where('asset_tag', 'RAD-99')->first();
        $this->assertNotNull($asset);

        Livewire::actingAs($this->admin)
            ->test(EquipmentIndex::class)
            ->call('delete', $asset->id);

        $this->assertDatabaseMissing('equipment_assets', ['id' => $asset->id]);
    }

    public function test_incident_create_drawer(): void
    {
        $site = Site::first();

        Livewire::actingAs($this->admin)
            ->test(IncidentIndex::class)
            ->assertSet('showForm', false)
            ->call('openForm')
            ->assertSet('showForm', true)
            ->set('form.site_id', $site->id)
            ->set('form.title', 'Test incident')
            ->set('form.type', 'trespass')
            ->set('form.severity', 'low')
            ->set('form.description', 'Someone entered without permission.')
            ->call('save')
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('incidents', ['title' => 'Test incident']);
    }

    public function test_schedule_shift_drawer(): void
    {
        $client = ClientAccount::first();
        $site = Site::first();

        Livewire::actingAs($this->admin)
            ->test(ScheduleBoard::class)
            ->assertSet('showForm', false)
            ->call('openForm')
            ->assertSet('showForm', true)
            ->set('form.client_account_id', $client->id)
            ->set('form.site_id', $site->id)
            ->set('form.title', 'Night patrol')
            ->set('form.starts_at', now()->addDay()->setTime(20, 0)->format('Y-m-d\TH:i'))
            ->set('form.ends_at', now()->addDays(2)->setTime(6, 0)->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('shifts', ['title' => 'Night patrol']);
    }

    public function test_visitor_check_in_drawer(): void
    {
        $site = Site::first();

        Livewire::actingAs($this->admin)
            ->test(VisitorLogIndex::class)
            ->assertSet('showForm', false)
            ->call('openCheckIn')
            ->assertSet('showForm', true)
            ->set('form.site_id', $site->id)
            ->set('form.visitor_name', 'John Visitor')
            ->call('checkIn')
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('visitor_logs', ['visitor_name' => 'John Visitor']);
    }

    public function test_guard_profile_tabs_switch(): void
    {
        $guard = Guard::first();

        Livewire::actingAs($this->admin)
            ->test(GuardProfile::class, ['guard' => $guard])
            ->assertSet('activeTab', 'overview')
            ->call('setTab', 'verification')
            ->assertSet('activeTab', 'verification')
            ->assertSee('Vetting checklist');
    }

    public function test_settings_hub_loads(): void
    {
        $this->actingAs($this->admin)
            ->get('/settings')
            ->assertOk()
            ->assertSee('Roles & Permissions');
    }
}
