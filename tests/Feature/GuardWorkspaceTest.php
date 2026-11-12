<?php

namespace Tests\Feature;

use App\Livewire\Guards\GuardIndex;
use App\Livewire\Guards\GuardApplicationQueue;
use App\Livewire\Guards\KnowYourGuardQueue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuardWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        $this->actingAs($admin);
        app()->instance('currentTenant', $admin->tenant);
    }

    public function test_tabs_render_review_content_inside_guard_workspace(): void
    {
        Livewire::test(GuardIndex::class)
            ->set('search', 'John')
            ->call('setTab', 'applications')
            ->assertSee('Guards & Officers')
            ->assertSeeLivewire(GuardApplicationQueue::class)
            ->call('setTab', 'verification')
            ->assertSeeLivewire(KnowYourGuardQueue::class)
            ->call('setTab', 'directory')
            ->assertSet('search', 'John')
            ->assertSee('John Mensah')
            ->assertDontSeeLivewire(KnowYourGuardQueue::class);
    }

    public function test_existing_review_links_open_the_shared_guard_workspace(): void
    {
        foreach (['guards.applications' => 'Public application link', 'guards.kyg' => 'Awaiting review'] as $route => $content) {
            $this->followingRedirects()->get(route($route))->assertOk()
                ->assertSee('Guards &amp; Officers', false)
                ->assertSee('Verification queue')
                ->assertSee($content);
        }
    }

    public function test_embedded_verification_does_not_inherit_directory_filters(): void
    {
        Livewire::withQueryParams(['search' => 'John', 'status' => 'active'])
            ->test(KnowYourGuardQueue::class, ['embedded' => true])
            ->assertSet('search', '')
            ->assertSet('statusFilter', 'queue')
            ->assertSee('Grace Okafor');
    }

    public function test_legacy_review_routes_redirect_to_bookmarkable_tabs(): void
    {
        $this->get(route('guards.applications'))->assertRedirect('/guards?tab=applications');
        $this->get(route('guards.kyg'))->assertRedirect('/guards?tab=verification');
    }

    public function test_switching_tabs_closes_the_roster_drawer(): void
    {
        Livewire::test(GuardIndex::class)->call('openCreate')
            ->call('setTab', 'applications')->assertSet('showForm', false);
    }
}
