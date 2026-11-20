<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\Overview as DashboardOverview;
use App\Livewire\Incidents\IncidentIndex;
use App\Livewire\Messenger\MessengerIndex;
use App\Models\Site;
use App\Models\User;
use App\Services\IncidentService;
use App\Services\NavigationBuilder;
use DOMDocument;
use DOMXPath;
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

    public function test_dashboard_prioritizes_the_roster_and_dispatch_before_quick_actions(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DashboardOverview::class)
            ->assertSeeInOrder(["Today's schedule", 'Open dispatches', 'Quick actions']);
    }

    public function test_application_shell_has_valid_navigation_semantics(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeHtml('aria-label="Close navigation"')
            ->assertSeeHtml('aria-haspopup="dialog"')
            ->getContent();

        $document = new DOMDocument;
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);

        $this->assertSame(0, $xpath->query('//a//button')->length);
        $this->assertGreaterThan(0, $xpath->query('//button[@aria-controls="workspace-command-palette"]')->length);
    }

    public function test_navigation_search_excludes_duplicate_destinations(): void
    {
        $this->actingAs($this->admin);
        $links = app(NavigationBuilder::class)->searchableLinks();
        $hrefs = array_column($links, 'href');

        $this->assertSame(count($hrefs), count(array_unique($hrefs)));
    }

    public function test_subscription_page_belongs_to_settings_not_back_office_navigation(): void
    {
        $this->actingAs($this->admin)
            ->get(route('billing.subscription'))
            ->assertOk();

        $nav = app(NavigationBuilder::class);

        $this->assertFalse($nav->isLinkActive(['href' => '/billing']));
        $this->assertTrue($nav->isLinkActive(['href' => '/settings']));
    }
}
