<?php

namespace Tests\Feature;

use App\Livewire\Guard\MobileDashboard;
use App\Livewire\Guards\GuardApplicationQueue;
use App\Livewire\Guards\GuardIndex;
use App\Livewire\Guards\KnowYourGuardQueue;
use App\Models\PatrolCheckpoint;
use App\Models\PatrolSession;
use App\Models\PostOrder;
use App\Models\ReportTemplate;
use App\Models\ShiftAssignment;
use App\Models\SitePost;
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

    public function test_guard_sees_active_site_and_assigned_post_orders_only(): void
    {
        $user = User::where('email', 'john.guard@test')->firstOrFail();
        setPermissionsTeamId($user->tenant_id);
        $assignment = ShiftAssignment::where('guard_id', $user->guardProfile->id)->firstOrFail();
        $shift = $assignment->shift;
        $shift->update(['starts_at' => today()->subHour(), 'ends_at' => now()->addHour()]);
        $otherPost = SitePost::create([
            'tenant_id' => $user->tenant_id, 'site_id' => $shift->site_id,
            'name' => 'Other post', 'required_guards' => 1, 'status' => 'active',
        ]);
        $data = ['tenant_id' => $user->tenant_id, 'site_id' => $shift->site_id, 'instructions' => 'Test instructions'];
        PostOrder::create($data + ['title' => 'Site safety procedure', 'is_active' => true]);
        PostOrder::create($data + ['title' => 'Assigned gate procedure', 'site_post_id' => $shift->site_post_id, 'is_active' => true]);
        PostOrder::create($data + ['title' => 'Other post procedure', 'site_post_id' => $otherPost->id, 'is_active' => true]);
        PostOrder::create($data + ['title' => 'Archived procedure', 'is_active' => false]);

        Livewire::actingAs($user)->test(MobileDashboard::class)
            ->set('activeAssignmentId', $assignment->id)
            ->assertSeeInOrder(['Post orders', 'Site safety procedure', 'Assigned gate procedure'])
            ->assertDontSee('Other post procedure')
            ->assertDontSee('Archived procedure')
            ->set('activeAssignmentId', 999999)
            ->assertDontSee('Site safety procedure');
    }

    public function test_guard_can_send_an_sos_without_location(): void
    {
        $user = User::where('email', 'john.guard@test')->firstOrFail();
        setPermissionsTeamId($user->tenant_id);

        Livewire::actingAs($user)->test(MobileDashboard::class)
            ->call('raiseSos')
            ->assertSee('SOS received by GuardCore Pro. Waiting for Control to acknowledge.');

        $this->assertDatabaseHas('sos_alerts', [
            'tenant_id' => $user->tenant_id,
            'guard_id' => $user->guardProfile->id,
            'status' => 'open',
            'latitude' => null,
            'longitude' => null,
        ]);
    }

    public function test_guard_without_a_shift_cannot_start_a_patrol(): void
    {
        $user = User::where('email', 'john.guard@test')->firstOrFail();
        setPermissionsTeamId($user->tenant_id);
        ShiftAssignment::where('guard_id', $user->guardProfile->id)->update(['status' => 'cancelled']);
        $checkpoint = PatrolCheckpoint::where('tenant_id', $user->tenant_id)->where('code', 'GATE-QR-001')->firstOrFail();

        $sessionCount = PatrolSession::count();

        Livewire::actingAs($user)->test(MobileDashboard::class)
            ->assertSet('activeAssignmentId', null)
            ->assertDontSee('Start new patrol')
            ->call('startPatrol', $checkpoint->patrol_route_id)
            ->assertHasErrors('action')
            ->assertSee('You need a scheduled shift at this site to start a patrol.');

        $this->assertDatabaseCount('patrol_sessions', $sessionCount);
    }

    public function test_guard_can_start_a_patrol_scan_a_checkpoint_and_save_passdown(): void
    {
        $user = User::where('email', 'john.guard@test')->firstOrFail();
        setPermissionsTeamId($user->tenant_id);
        $assignment = ShiftAssignment::where('guard_id', $user->guardProfile->id)->firstOrFail();
        $checkpoint = PatrolCheckpoint::where('tenant_id', $user->tenant_id)->where('code', 'GATE-QR-001')->firstOrFail();

        $component = Livewire::actingAs($user)->test(MobileDashboard::class)
            ->set('activeAssignmentId', null)
            ->call('startPatrol', $checkpoint->patrol_route_id)
            ->assertSee('Patrol started')
            ->call('onQrScanned', $checkpoint->code)
            ->assertSet('checkpointCode', $checkpoint->code)
            ->set('latitude', 6.206)
            ->set('longitude', -1.665)
            ->set('checkpointCode', 'UNKNOWN-CODE')
            ->call('scanCheckpoint')
            ->assertHasErrors('checkpointCode')
            ->set('checkpointCode', $checkpoint->code)
            ->call('scanCheckpoint')
            ->assertSee('Checkpoint scanned.')
            ->set('activeAssignmentId', $assignment->id)
            ->set('passdownContent', 'The main gate was secured.')
            ->call('savePassdown')
            ->assertSee('Passdown saved.');

        $this->assertDatabaseHas('patrol_sessions', [
            'id' => $component->get('patrolSessionId'),
            'shift_assignment_id' => $assignment->id,
        ]);
        $this->assertDatabaseHas('checkpoint_scans', [
            'patrol_session_id' => $component->get('patrolSessionId'),
            'patrol_checkpoint_id' => $checkpoint->id,
        ]);
        $this->assertDatabaseHas('passdown_logs', [
            'shift_assignment_id' => $assignment->id,
            'content' => 'The main gate was secured.',
        ]);
    }

    public function test_guard_cannot_submit_a_scan_without_an_active_patrol(): void
    {
        $user = User::where('email', 'john.guard@test')->firstOrFail();
        setPermissionsTeamId($user->tenant_id);
        PatrolSession::where('guard_id', $user->guardProfile->id)->update(['status' => 'completed']);

        Livewire::actingAs($user)->test(MobileDashboard::class)
            ->assertSee('No active patrol.')
            ->set('checkpointCode', 'GATE-QR-001')
            ->call('scanCheckpoint')
            ->assertHasErrors('patrolSessionId')
            ->assertSee('Select an active patrol.');
    }

    public function test_guard_report_requires_eligible_template_and_required_fields(): void
    {
        $user = User::where('email', 'john.guard@test')->firstOrFail();
        setPermissionsTeamId($user->tenant_id);
        $template = ReportTemplate::where('tenant_id', $user->tenant_id)->whereHas('fields')->firstOrFail();
        $field = $template->fields()->where('is_required', true)->firstOrFail();

        Livewire::actingAs($user)->test(MobileDashboard::class)
            ->set('activeReportTemplateId', 999999)
            ->call('saveReportDraft')
            ->assertHasErrors('action')
            ->set('activeReportTemplateId', $template->id)
            ->call('submitCustomReport')
            ->assertHasErrors('reportData.'.$field->id)
            ->set('reportData.'.$field->id, 'yes')
            ->call('submitCustomReport')
            ->assertHasNoErrors()
            ->assertSee('Custom report submitted.');
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
