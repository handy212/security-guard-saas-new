<?php

namespace Tests\Feature;

use App\Livewire\Assets\Overview as AssetsOverview;
use App\Livewire\Incidents\IncidentIndex;
use App\Livewire\Messenger\MessengerIndex;
use App\Models\Incident;
use App\Models\Site;
use App\Models\User;
use App\Services\IncidentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OpsSurfacesPolishTest extends TestCase
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

    public function test_incident_detail_drawer_opens(): void
    {
        $site = Site::where('tenant_id', $this->admin->tenant_id)->firstOrFail();
        $incident = app(IncidentService::class)->submit([
            'tenant_id' => $this->admin->tenant_id,
            'site_id' => $site->id,
            'reported_by_user_id' => $this->admin->id,
            'title' => 'Gate alarm',
            'type' => 'alarm',
            'severity' => 'high',
            'description' => 'Rear gate alarm triggered.',
        ]);

        Livewire::actingAs($this->admin)
            ->test(IncidentIndex::class)
            ->call('viewIncident', $incident->id)
            ->assertSet('showDetail', true)
            ->assertSet('viewingIncidentId', $incident->id)
            ->assertSee('Rear gate alarm triggered.');
    }

    public function test_incident_approve_only_from_submitted(): void
    {
        $site = Site::where('tenant_id', $this->admin->tenant_id)->firstOrFail();
        $incident = app(IncidentService::class)->submit([
            'tenant_id' => $this->admin->tenant_id,
            'site_id' => $site->id,
            'reported_by_user_id' => $this->admin->id,
            'title' => 'Trespass',
            'type' => 'trespass',
            'severity' => 'medium',
            'description' => 'Unknown person near fence.',
        ]);

        Livewire::actingAs($this->admin)
            ->test(IncidentIndex::class)
            ->call('approve', $incident->id)
            ->assertHasNoErrors();

        $this->assertSame('approved', $incident->fresh()->status);
    }

    public function test_messenger_creates_thread_with_participants(): void
    {
        $site = Site::where('tenant_id', $this->admin->tenant_id)->firstOrFail();

        Livewire::actingAs($this->admin)
            ->test(MessengerIndex::class)
            ->call('openCreateThread')
            ->set('threadForm.subject', 'Night shift check-in')
            ->set('threadForm.site_id', (string) $site->id)
            ->set('threadForm.participant_ids', [$this->admin->id])
            ->call('createThread')
            ->assertHasNoErrors()
            ->assertSet('showForm', false);

        $this->assertDatabaseHas('message_threads', [
            'tenant_id' => $this->admin->tenant_id,
            'subject' => 'Night shift check-in',
            'site_id' => $site->id,
        ]);
    }

    public function test_assets_overview_renders_with_links(): void
    {
        $this->actingAs($this->admin)
            ->get(route('assets.overview'))
            ->assertOk()
            ->assertSee('Total assets')
            ->assertSee(route('assets.index', absolute: false));
    }

    public function test_messenger_page_renders(): void
    {
        Livewire::actingAs($this->admin)
            ->test(MessengerIndex::class)
            ->assertSee('Messenger')
            ->assertSee('New thread');
    }
}
