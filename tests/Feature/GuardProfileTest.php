<?php

namespace Tests\Feature;

use App\Livewire\Guards\GuardProfile;
use App\Models\Guard;
use App\Models\GuardNote;
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
