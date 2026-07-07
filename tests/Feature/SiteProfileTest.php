<?php

namespace Tests\Feature;

use App\Livewire\Sites\SiteProfile;
use App\Models\ClientAccount;
use App\Models\Site;
use App\Models\SiteEmergencyContact;
use App\Models\SiteNote;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SiteProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@demo.test')->first();
        app()->instance('currentTenant', $this->admin->tenant);
    }

    public function test_site_profile_page_loads_with_overview(): void
    {
        $site = Site::first();

        $this->actingAs($this->admin)
            ->get(route('sites.show', $site))
            ->assertOk()
            ->assertSee($site->name)
            ->assertSee('Overview')
            ->assertSee('Post Orders')
            ->assertSee('Geo-Fence')
            ->assertSee('Email Reports');
    }

    public function test_site_profile_tabs_switch(): void
    {
        $site = Site::first();

        Livewire::actingAs($this->admin)
            ->test(SiteProfile::class, ['site' => $site])
            ->call('setTab', 'contacts')
            ->assertSet('activeTab', 'contacts')
            ->assertSee('Add contact');
    }

    public function test_site_contact_crud_via_profile(): void
    {
        $site = Site::first();

        Livewire::actingAs($this->admin)
            ->test(SiteProfile::class, ['site' => $site])
            ->call('setTab', 'contacts')
            ->set('contactForm.name', 'Site Security Lead')
            ->set('contactForm.phone', '+233201234567')
            ->set('contactForm.role', 'Supervisor')
            ->call('saveContact')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('site_emergency_contacts', [
            'site_id' => $site->id,
            'name' => 'Site Security Lead',
            'phone' => '+233201234567',
        ]);

        $contact = SiteEmergencyContact::where('phone', '+233201234567')->first();

        Livewire::actingAs($this->admin)
            ->test(SiteProfile::class, ['site' => $site->fresh()])
            ->call('deleteContact', $contact->id);

        $this->assertDatabaseMissing('site_emergency_contacts', ['id' => $contact->id]);
    }

    public function test_site_note_crud_via_profile(): void
    {
        $site = Site::first();

        Livewire::actingAs($this->admin)
            ->test(SiteProfile::class, ['site' => $site])
            ->call('setTab', 'notes')
            ->set('noteForm.body', 'Gate code changed to 4521.')
            ->call('addNote')
            ->assertHasNoErrors();

        $note = SiteNote::where('site_id', $site->id)->where('body', 'Gate code changed to 4521.')->first();
        $this->assertNotNull($note);

        Livewire::actingAs($this->admin)
            ->test(SiteProfile::class, ['site' => $site->fresh()])
            ->call('deleteNote', $note->id);

        $this->assertDatabaseMissing('site_notes', ['id' => $note->id]);
    }

    public function test_site_profile_rejects_other_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherClient = ClientAccount::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Client',
            'status' => 'active',
        ]);
        $otherSite = Site::create([
            'tenant_id' => $otherTenant->id,
            'client_account_id' => $otherClient->id,
            'name' => 'Foreign Site',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->get(route('sites.show', $otherSite))
            ->assertNotFound();
    }

    public function test_sites_index_links_to_profile(): void
    {
        $site = Site::first();

        $this->actingAs($this->admin)
            ->get(route('sites.index'))
            ->assertOk()
            ->assertSee(route('sites.show', $site), false);
    }

    public function test_site_settings_save(): void
    {
        $site = Site::first();

        Livewire::actingAs($this->admin)
            ->test(SiteProfile::class, ['site' => $site])
            ->call('setTab', 'settings')
            ->set('settingsForm.require_geofence_clock_in', false)
            ->set('settingsForm.patrol_reminder_minutes', 45)
            ->call('saveSettings')
            ->assertHasNoErrors();

        $site->refresh();
        $this->assertFalse($site->resolvedSettings()['require_geofence_clock_in']);
        $this->assertSame(45, $site->resolvedSettings()['patrol_reminder_minutes']);
    }
}
