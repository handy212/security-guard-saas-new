<?php

namespace Tests\Feature;

use App\Livewire\Schedules\DeploymentSheet;
use App\Livewire\Schedules\DeployWizard;
use App\Models\EquipmentAsset;
use App\Models\Guard;
use App\Models\GuardAvailability;
use App\Models\Shift;
use App\Models\Site;
use App\Models\SitePost;
use App\Models\User;
use App\Services\AssetManagementService;
use App\Services\ScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class DeploymentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Guard $guard;

    private Shift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@demo.test')->firstOrFail();
        app()->instance('currentTenant', $this->admin->tenant);
        setPermissionsTeamId($this->admin->tenant_id);
        $this->actingAs($this->admin);
        $site = Site::where('tenant_id', $this->admin->tenant_id)->firstOrFail();
        $this->guard = Guard::create([
            'tenant_id' => $this->admin->tenant_id,
            'first_name' => 'Deployment',
            'last_name' => 'Guard',
            'employee_number' => 'DEPLOY-INTEGRITY',
            'status' => 'active',
            'verification_status' => 'verified',
        ]);
        $this->shift = app(ScheduleService::class)->createShift([
            'tenant_id' => $this->admin->tenant_id,
            'client_account_id' => $site->client_account_id,
            'site_id' => $site->id,
            'title' => 'Deployment coverage',
            'starts_at' => now()->addDays(30)->setTime(8, 0),
            'ends_at' => now()->addDays(30)->setTime(16, 0),
            'required_guards' => 2,
        ]);
    }

    public function test_duplicate_guard_cannot_fill_a_second_position(): void
    {
        $service = app(ScheduleService::class);
        $service->assignGuard($this->shift, $this->guard);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('already assigned to this shift');
        $service->assignGuard($this->shift->fresh(), $this->guard);
    }

    public function test_no_show_position_can_be_filled_by_a_replacement(): void
    {
        $this->shift->update(['required_guards' => 1]);
        $service = app(ScheduleService::class);
        $assignment = $service->assignGuard($this->shift, $this->guard);
        $assignment->update(['status' => 'no_show']);
        $replacement = $this->guard->replicate();
        $replacement->employee_number = 'DEPLOY-REPLACEMENT';
        $replacement->save();

        $this->assertSame(0, $this->shift->fresh()->activeAssignmentsCount());
        Livewire::actingAs($this->admin)
            ->test(DeploymentSheet::class, ['date' => $this->shift->starts_at->toDateString()])
            ->assertViewHas('understaffed', fn ($shifts) => $shifts->contains('id', $this->shift->id))
            ->set("pendingGuard.{$this->shift->id}", (string) $replacement->id)
            ->call('assignToShift', $this->shift->id)
            ->assertHasNoErrors()
            ->assertViewHas('understaffed', fn ($shifts) => ! $shifts->contains('id', $this->shift->id));
        $replacementAssignment = $this->shift->assignments()->where('guard_id', $replacement->id)->firstOrFail();

        $this->assertSame($replacement->id, $replacementAssignment->guard_id);
        $this->assertSame(1, $this->shift->fresh()->activeAssignmentsCount());
        $this->assertSame('assigned', $this->shift->fresh()->status->value);
    }

    public function test_inactive_guard_is_rejected_even_with_a_stale_model(): void
    {
        Guard::whereKey($this->guard->id)->update(['status' => 'inactive']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('active');
        app(ScheduleService::class)->assignGuard($this->shift, $this->guard);
    }

    public function test_cancelled_shift_is_rejected_even_with_a_stale_model(): void
    {
        Shift::whereKey($this->shift->id)->update(['status' => 'cancelled']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cancelled or completed');
        app(ScheduleService::class)->assignGuard($this->shift, $this->guard);
    }

    public function test_completed_shift_cannot_be_reopened_by_assignment(): void
    {
        $this->shift->update(['status' => 'completed']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('cancelled or completed');
        app(ScheduleService::class)->assignGuard($this->shift, $this->guard);
    }

    public function test_inventory_reads_do_not_recreate_deleted_sample_equipment(): void
    {
        $tenantId = $this->admin->tenant_id;
        EquipmentAsset::where('tenant_id', $tenantId)->where('asset_tag', 'CAM-001')->delete();
        $assets = app(AssetManagementService::class);

        $this->assertFalse($assets->availableDeployKit($tenantId)->contains('asset_tag', 'CAM-001'));
        $assets->ensureDeployKitCatalog($tenantId);
        $assets->deployKitSummary($tenantId);

        $this->assertDatabaseMissing('equipment_assets', ['tenant_id' => $tenantId, 'asset_tag' => 'CAM-001']);
    }

    public function test_reassignment_and_unassignment_keep_kit_with_original_guard_until_received(): void
    {
        $schedules = app(ScheduleService::class);
        $assets = app(AssetManagementService::class);
        $assignment = $schedules->assignGuard($this->shift, $this->guard);
        $camera = EquipmentAsset::where('asset_tag', 'CAM-001')->firstOrFail();
        $issue = $assets->issueKitForAssignment($assignment, [$camera->id])->first();
        $replacement = $this->guard->replicate();
        $replacement->employee_number = 'DEPLOY-HANDOVER';
        $replacement->save();

        $newAssignment = $schedules->reassignGuard($assignment, $replacement);
        $schedules->unassignGuard($newAssignment);

        $this->assertSame('issued', $issue->fresh()->status);
        $this->assertNull($issue->fresh()->returned_at);
        $this->assertSame($this->guard->id, $issue->fresh()->guard_id);
        $this->assertFalse($assets->availableDeployKit($this->admin->tenant_id)->contains('id', $camera->id));

        $assets->returnAsset($issue, 'Received from original guard');

        $this->assertSame('returned', $issue->fresh()->status);
        $this->assertTrue($assets->availableDeployKit($this->admin->tenant_id)->contains('id', $camera->id));
    }

    public function test_unavailable_guard_is_rejected_at_guard_selection_and_returns_to_guard_on_submission(): void
    {
        GuardAvailability::create([
            'tenant_id' => $this->admin->tenant_id,
            'guard_id' => $this->guard->id,
            'weekday' => $this->shift->starts_at->dayOfWeek,
            'starts_at' => '06:00',
            'ends_at' => '07:00',
            'is_available' => true,
        ]);

        $this->wizard()
            ->set('step', 3)
            ->call('nextStep')
            ->assertSet('step', 3)
            ->assertHasErrors('guard_id')
            ->assertSee('Guard is not available')
            ->set('step', 5)
            ->call('deploy')
            ->assertSet('step', 3)
            ->assertHasErrors('guard_id')
            ->assertHasNoErrors('selectedAssetIds');

        $this->assertSame(0, $this->shift->assignments()->count());
    }

    public function test_full_shift_is_not_offered_and_cannot_advance_past_shift_selection(): void
    {
        $this->shift->update(['required_guards' => 1]);
        app(ScheduleService::class)->assignGuard($this->shift, $this->guard);

        $this->wizard()
            ->assertViewHas('shifts', fn ($shifts) => ! $shifts->contains('id', $this->shift->id))
            ->set('step', 2)
            ->call('nextStep')
            ->assertSet('step', 2)
            ->assertHasErrors('shift_id')
            ->set('step', 5)
            ->call('deploy')
            ->assertSet('step', 2)
            ->assertHasErrors('shift_id');
    }

    public function test_post_filter_is_honoured_and_review_uses_the_actual_shift_post_and_dates(): void
    {
        $post = SitePost::create([
            'tenant_id' => $this->admin->tenant_id,
            'site_id' => $this->shift->site_id,
            'name' => 'Actual deployment post',
        ]);
        $otherPost = $post->replicate();
        $otherPost->name = 'Other deployment post';
        $otherPost->save();
        $this->shift->update(['site_post_id' => $post->id]);

        $this->wizard()
            ->set('site_post_id', (string) $otherPost->id)
            ->assertViewHas('shifts', fn ($shifts) => ! $shifts->contains('id', $this->shift->id))
            ->set('site_post_id', '')
            ->set('step', 5)
            ->assertSee('Actual deployment post')
            ->assertSee($this->shift->starts_at->format('M j, Y H:i'))
            ->assertSee($this->shift->ends_at->format('M j, Y H:i'));
    }

    public function test_refresh_with_later_step_url_starts_at_site_with_consistent_client_context(): void
    {
        Livewire::actingAs($this->admin)
            ->withQueryParams(['step' => 5, 'site' => $this->shift->site_id])
            ->test(DeployWizard::class)
            ->assertSet('step', 1)
            ->assertSet('client_account_id', (string) $this->shift->client_account_id)
            ->assertSee('Where are they going?');
    }

    public function test_final_submission_rejects_a_shift_from_a_different_site(): void
    {
        $otherSite = Site::where('id', '!=', $this->shift->site_id)->firstOrFail();

        $this->wizard()
            ->set('site_id', (string) $otherSite->id)
            ->set('shift_id', (string) $this->shift->id)
            ->set('step', 5)
            ->call('deploy')
            ->assertSet('step', 2)
            ->assertHasErrors('shift_id');

        $this->assertSame(0, $this->shift->assignments()->count());
    }

    public function test_unfinished_deployment_can_resume_after_refresh(): void
    {
        $date = $this->shift->starts_at->toDateString();
        Livewire::actingAs($this->admin)->test(DeployWizard::class)
            ->set('date', $date)
            ->set('site_id', (string) $this->shift->site_id)
            ->call('nextStep')
            ->assertSet('step', 2);

        Livewire::actingAs($this->admin)->withQueryParams(['date' => $date])->test(DeployWizard::class)
            ->assertSee('Resume draft')
            ->call('resumeDraft')
            ->assertSet('step', 2)
            ->assertSet('site_id', (string) $this->shift->site_id)
            ->call('resetWizard')
            ->assertDontSee('Resume draft');
    }

    public function test_pending_success_explains_confirmation_and_remaining_coverage(): void
    {
        $this->wizard()
            ->set('confirm_now', false)
            ->set('step', 5)
            ->call('deploy')
            ->assertHasNoErrors()
            ->assertSet('step', 6)
            ->assertSee('Awaiting confirmation')
            ->assertSee('1 of 2 positions filled')
            ->assertSee('1 position still needs a guard');
    }

    public function test_wizard_controls_have_associated_labels_and_accessible_validation(): void
    {
        $wizard = Livewire::actingAs($this->admin)->test(DeployWizard::class);
        $document = new \DOMDocument;
        $document->loadHTML($wizard->html(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        foreach (['Deployment date', 'Client', 'Site', 'Post (optional)'] as $label) {
            $labelNode = $xpath->query("//label[normalize-space(.)='{$label}']")->item(0);
            $this->assertNotNull($labelNode);
            $id = $labelNode->getAttribute('for');
            $this->assertNotSame('', $id);
            $this->assertSame(1, $xpath->query("//*[@id='{$id}']")->length);
        }

        $wizard->call('nextStep')
            ->assertHasErrors('client_account_id')
            ->assertSeeHtml('aria-invalid="true"')
            ->assertSeeHtml('aria-describedby=');
    }

    public function test_step_and_selected_kit_are_exposed_to_assistive_technology(): void
    {
        $camera = EquipmentAsset::where('asset_tag', 'CAM-001')->firstOrFail();

        $this->wizard()
            ->set('step', 4)
            ->call('toggleAsset', $camera->id)
            ->assertSeeHtml('aria-current="step"')
            ->assertSeeHtml('aria-pressed="true"');
    }

    public function test_new_shift_rejects_a_post_from_another_site(): void
    {
        $otherPost = SitePost::create([
            'tenant_id' => $this->admin->tenant_id,
            'site_id' => Site::where('id', '!=', $this->shift->site_id)->firstOrFail()->id,
            'name' => 'Different site post',
        ]);
        $shiftCount = Shift::count();

        $this->wizard()
            ->set('shift_mode', 'new')
            ->set('site_post_id', (string) $otherPost->id)
            ->set('step', 5)
            ->call('deploy')
            ->assertSet('step', 1)
            ->assertHasErrors('site_post_id');

        $this->assertSame($shiftCount, Shift::count());
    }

    public function test_kit_failure_returns_to_kit_and_rolls_back_shift_assignment_and_earlier_issues(): void
    {
        $camera = EquipmentAsset::where('asset_tag', 'CAM-001')->firstOrFail();
        $issuedRadio = EquipmentAsset::where('asset_tag', 'RAD-001')->firstOrFail();
        $shiftCount = Shift::count();

        $this->wizard()
            ->set('shift_mode', 'new')
            ->set('selectedAssetIds', [$camera->id, $issuedRadio->id])
            ->set('step', 5)
            ->call('deploy')
            ->assertSet('step', 4)
            ->assertHasErrors('selectedAssetIds')
            ->assertHasNoErrors('guard_id');

        $this->assertSame($shiftCount, Shift::count());
        $this->assertSame(0, $this->guard->assignments()->count());
        $this->assertSame('available', $camera->fresh()->status->value);
    }

    public function test_deployment_sheet_explains_overnight_end_dates_in_roster_and_staffing_gaps(): void
    {
        $this->shift->update([
            'starts_at' => $this->shift->starts_at->copy()->setTime(18, 0),
            'ends_at' => $this->shift->starts_at->copy()->addDay()->setTime(6, 0),
        ]);
        app(ScheduleService::class)->assignGuard($this->shift, $this->guard);
        $endLabel = 'Ends '.$this->shift->ends_at->format('M j, Y');

        $sheet = Livewire::actingAs($this->admin)
            ->test(DeploymentSheet::class, ['date' => $this->shift->starts_at->toDateString()])
            ->assertSee($endLabel);

        $this->assertSame(2, substr_count($sheet->html(), $endLabel));

        $this->shift->update(['ends_at' => $this->shift->starts_at->copy()->setTime(23, 0)]);
        $sheet->call('$refresh')->assertDontSee('Ends ');
    }

    public function test_deployment_sheet_row_actions_and_reassignment_identify_their_context(): void
    {
        $assignment = app(ScheduleService::class)->assignGuard($this->shift, $this->guard);

        Livewire::actingAs($this->admin)
            ->test(DeploymentSheet::class, ['date' => $this->shift->starts_at->toDateString()])
            ->assertSeeHtml('aria-label="Confirm assignment for Deployment Guard"')
            ->assertSeeHtml('aria-label="Reassign Deployment Guard"')
            ->assertSeeHtml('aria-label="Unassign Deployment Guard"')
            ->call('openReassign', $assignment->id)
            ->assertSeeHtml('aria-label="Reassign guard"');
    }

    private function wizard(): Testable
    {
        return Livewire::actingAs($this->admin)
            ->test(DeployWizard::class)
            ->set('date', $this->shift->starts_at->toDateString())
            ->set('client_account_id', (string) $this->shift->client_account_id)
            ->set('site_id', (string) $this->shift->site_id)
            ->set('shift_id', (string) $this->shift->id)
            ->set('guard_id', (string) $this->guard->id);
    }
}
