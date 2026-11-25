<?php

namespace Tests\Feature;

use App\Livewire\Guards\GuardProfile;
use App\Models\Guard;
use App\Models\GuardCertification;
use App\Models\GuardNote;
use App\Models\ShiftAssignment;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuardProfileTest extends TestCase
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

    public function test_qualification_drawer_validates_saves_and_closes(): void
    {
        $guard = Guard::first();
        Livewire::actingAs($this->admin)->test(GuardProfile::class, ['guard' => $guard])
            ->call('setTab', 'qualifications')
            ->assertDontSee('wire:submit="saveCertification"', false)
            ->call('openQualificationDrawer', 'certification')
            ->assertSet('qualificationDrawer', 'certification')
            ->call('saveCertification')->assertHasErrors(['certForm.name'])
            ->assertSet('qualificationDrawer', 'certification')
            ->set('certForm.name', 'Radio operations')
            ->call('saveCertification')->assertHasNoErrors()
            ->assertSet('qualificationDrawer', null)
            ->assertSee('Radio operations')
            ->call('openQualificationDrawer', 'training')
            ->call('closeQualificationDrawer')
            ->assertSet('qualificationDrawer', null);
    }

    public function test_mobile_tab_change_clears_qualification_drawer_and_validation(): void
    {
        Livewire::actingAs($this->admin)->test(GuardProfile::class, ['guard' => Guard::first()])
            ->call('setTab', 'qualifications')
            ->call('openQualificationDrawer', 'certification')
            ->call('saveCertification')->assertHasErrors(['certForm.name'])
            ->set('activeTab', 'hr')
            ->assertSet('qualificationDrawer', null)
            ->assertHasNoErrors()
            ->set('activeTab', 'qualifications')
            ->assertDontSee('wire:submit="saveCertification"', false);
    }

    public function test_guard_profile_page_loads_with_overview(): void
    {
        $guard = Guard::first();

        $this->actingAs($this->admin)
            ->get(route('guards.show', $guard))
            ->assertOk()
            ->assertSee($guard->full_name)
            ->assertSee('Overview')
            ->assertSee('Availability')
            ->assertSee('Qualifications')
            ->assertSee('HR');
    }

    public function test_guard_profile_tabs_switch(): void
    {
        $guard = Guard::first();

        Livewire::actingAs($this->admin)
            ->test(GuardProfile::class, ['guard' => $guard])
            ->call('setTab', 'notes')
            ->assertSet('activeTab', 'hr')
            ->assertSee('Add note');
    }

    public function test_expired_certifications_are_labeled_expired(): void
    {
        $guard = Guard::first();
        GuardCertification::create([
            'tenant_id' => $guard->tenant_id,
            'guard_id' => $guard->id,
            'name' => 'First aid',
            'issuer' => 'Training provider',
            'expires_at' => now()->subDay()->toDateString(),
            'status' => 'active',
        ]);

        Livewire::actingAs($this->admin)
            ->test(GuardProfile::class, ['guard' => $guard])
            ->call('setTab', 'qualifications')
            ->assertSee('Expired');
    }

    public function test_guard_note_crud_via_profile(): void
    {
        $guard = Guard::first();

        Livewire::actingAs($this->admin)
            ->test(GuardProfile::class, ['guard' => $guard])
            ->call('setTab', 'hr')
            ->set('noteForm.body', 'Completed annual refresher training.')
            ->call('addNote')
            ->assertHasNoErrors();

        $note = GuardNote::where('guard_id', $guard->id)->where('body', 'Completed annual refresher training.')->first();
        $this->assertNotNull($note);

        Livewire::actingAs($this->admin)
            ->test(GuardProfile::class, ['guard' => $guard->fresh()])
            ->call('deleteNote', $note->id);

        $this->assertDatabaseMissing('guard_notes', ['id' => $note->id]);
    }

    public function test_sites_tab_includes_sites_from_shift_assignments(): void
    {
        $assignment = ShiftAssignment::with('shift.site')->firstOrFail();
        $assignment->update(['status' => 'assigned']);
        $assignment->shift->update(['status' => 'scheduled', 'ends_at' => now()->addHour()]);
        $guard = Guard::findOrFail($assignment->guard_id);
        $site = $assignment->shift->site;
        $guard->siteAssignments()->delete();

        Livewire::actingAs($this->admin)
            ->withQueryParams(['tab' => 'sites'])
            ->test(GuardProfile::class, ['guard' => $guard])
            ->assertSet('activeTab', 'sites')
            ->assertSee('Scheduled shift')
            ->assertSeeHtml(route('sites.show', $site))
            ->assertDontSee('No sites assigned.');
    }

    public function test_guard_site_assignment_via_profile(): void
    {
        $guard = Guard::first();
        $site = Site::first();

        Livewire::actingAs($this->admin)
            ->test(GuardProfile::class, ['guard' => $guard])
            ->call('setTab', 'sites')
            ->set('siteAssignForm.site_id', (string) $site->id)
            ->set('siteAssignForm.is_primary', true)
            ->call('assignSite')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('guard_site_assignments', [
            'guard_id' => $guard->id,
            'site_id' => $site->id,
            'is_primary' => true,
        ]);
    }

    public function test_guard_profile_rejects_other_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherGuard = Guard::create([
            'tenant_id' => $otherTenant->id,
            'first_name' => 'Foreign',
            'last_name' => 'Guard',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->get(route('guards.show', $otherGuard))
            ->assertNotFound();
    }

    public function test_guard_settings_save(): void
    {
        $guard = Guard::first();

        Livewire::actingAs($this->admin)
            ->test(GuardProfile::class, ['guard' => $guard])
            ->call('setTab', 'settings')
            ->assertSet('activeTab', 'profile')
            ->set('settingsForm.allow_open_shift_bids', false)
            ->set('settingsForm.preferred_contact_method', 'email')
            ->call('saveSettings')
            ->assertHasNoErrors();

        $guard->refresh();
        $this->assertFalse($guard->resolvedSettings()['allow_open_shift_bids']);
        $this->assertSame('email', $guard->resolvedSettings()['preferred_contact_method']);
    }
}
