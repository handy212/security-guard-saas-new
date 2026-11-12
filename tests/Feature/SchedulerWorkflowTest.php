<?php

namespace Tests\Feature;

use App\Livewire\Attendance\ReconciliationBoard;
use App\Livewire\Scheduling\AttendanceIndex;
use App\Livewire\Scheduling\OpenShiftsIndex;
use App\Livewire\Scheduling\ScheduleIndex;
use App\Livewire\Scheduling\ShiftExchangeIndex;
use App\Livewire\Scheduling\ShiftTemplateIndex;
use App\Livewire\Scheduling\TimeOffIndex;
use App\Models\AttendanceLog;
use App\Models\Guard;
use App\Models\LeaveRequest;
use App\Models\OpenShiftBid;
use App\Models\Shift;
use App\Models\ShiftSwapRequest;
use App\Models\ShiftTemplate;
use App\Models\Site;
use App\Models\SitePost;
use App\Models\User;
use App\Services\ScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchedulerWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Guard $guard;

    private Shift $shift;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@demo.test')->firstOrFail();
        $this->actingAs($this->admin);
        app()->instance('currentTenant', $this->admin->tenant);
        $this->guard = Guard::create(['tenant_id' => $this->admin->tenant_id, 'first_name' => 'Workflow', 'last_name' => 'Guard', 'status' => 'active', 'verification_status' => 'verified']);
        $site = Site::firstOrFail();
        $this->shift = app(ScheduleService::class)->createShift([
            'tenant_id' => $this->admin->tenant_id, 'client_account_id' => $site->client_account_id,
            'site_id' => $site->id, 'title' => 'Workflow audit',
            'starts_at' => '2030-06-10 08:00', 'ends_at' => '2030-06-10 16:00', 'required_guards' => 1,
        ]);
    }

    public function test_roster_create_edit_assign_and_cancel(): void
    {
        $page = Livewire::test(ScheduleIndex::class, ['embedded' => true, 'date' => '2030-06-11'])
            ->call('openForm')->call('save')->assertHasErrors(['form.title', 'form.site_id'])
            ->set('form.client_account_id', (string) $this->shift->client_account_id)
            ->set('form.site_id', (string) $this->shift->site_id)->set('form.title', 'Audit created shift')
            ->call('save')->assertHasNoErrors()->assertSet('showForm', false)->assertSee('Audit created shift');
        $shift = Shift::where('title', 'Audit created shift')->firstOrFail();
        $page->call('editShift', $shift->id)->set('form.title', 'Audit edited shift')->call('save')
            ->assertHasNoErrors()->assertSee('Audit edited shift')
            ->set('pendingGuard.'.$shift->id, $this->guard->id)->call('assignGuard', $shift->id)->assertHasNoErrors();
        $this->assertSame(1, $shift->fresh()->activeAssignmentsCount());
        $page->call('cancelShift', $shift->id);
        $this->assertSame('cancelled', $shift->fresh()->status->value);
        $this->assertSame(0, $shift->fresh()->activeAssignmentsCount());
    }

    public function test_bid_approval_rejects_approved_leave_without_changing_the_bid(): void
    {
        LeaveRequest::create(['tenant_id' => $this->admin->tenant_id, 'guard_id' => $this->guard->id, 'type' => 'annual', 'starts_on' => '2030-06-10', 'ends_on' => '2030-06-10', 'status' => 'approved']);
        $bid = OpenShiftBid::create(['tenant_id' => $this->admin->tenant_id, 'shift_id' => $this->shift->id, 'guard_id' => $this->guard->id, 'status' => 'pending']);
        Livewire::test(OpenShiftsIndex::class)->call('approveBid', $bid->id)->assertSee('approved leave');
        $this->assertSame('pending', $bid->fresh()->status->value);
        $this->assertSame(0, $this->shift->fresh()->activeAssignmentsCount());
    }

    public function test_valid_bid_can_be_approved_only_once(): void
    {
        $bid = OpenShiftBid::create(['tenant_id' => $this->admin->tenant_id, 'shift_id' => $this->shift->id, 'guard_id' => $this->guard->id, 'status' => 'pending']);
        $page = Livewire::test(OpenShiftsIndex::class)->call('approveBid', $bid->id)->assertSee('Bid approved');
        $page->call('approveBid', $bid->id)->assertSee('already been reviewed');
        $this->assertSame(1, $this->shift->fresh()->activeAssignmentsCount());
    }

    public function test_swap_failure_keeps_original_assignment_and_shows_feedback(): void
    {
        $assignment = app(ScheduleService::class)->assignGuard($this->shift, $this->guard);
        $replacement = Guard::create(['tenant_id' => $this->admin->tenant_id, 'first_name' => 'Replacement', 'last_name' => 'Officer', 'status' => 'inactive', 'verification_status' => 'verified']);
        $swap = ShiftSwapRequest::create(['tenant_id' => $this->admin->tenant_id, 'shift_assignment_id' => $assignment->id, 'requested_by_guard_id' => $this->guard->id, 'replacement_guard_id' => $replacement->id, 'status' => 'pending']);
        Livewire::test(ShiftExchangeIndex::class)->call('approveSwap', $swap->id)->assertSee('active');
        $this->assertSame('pending', $swap->fresh()->status->value);
        $this->assertSame('assigned', $assignment->fresh()->status->value);
        $this->assertSame($this->guard->id, $assignment->fresh()->guard_id);
    }

    public function test_attendance_shows_no_show_records_without_a_clock_in(): void
    {
        $this->attendance('no_show', false);
        Livewire::test(AttendanceIndex::class, ['date' => '2030-06-10'])->assertSee($this->guard->full_name);
    }

    public function test_break_can_be_saved_without_an_end_time(): void
    {
        $log = $this->attendance('on_time');
        Livewire::test(AttendanceIndex::class, ['date' => '2030-06-10'])->call('openBreakForm', $log->id)
            ->set('breakForm.started_at', '2030-06-10T12:00')->call('saveBreak')->assertHasNoErrors()->assertSet('showBreakForm', false);
        $this->assertDatabaseHas('break_logs', ['attendance_log_id' => $log->id, 'ended_at' => null]);
    }

    public function test_break_cannot_start_before_clock_in(): void
    {
        $log = $this->attendance('on_time');
        Livewire::test(AttendanceIndex::class, ['date' => '2030-06-10'])->call('openBreakForm', $log->id)
            ->set('breakForm.started_at', '2030-06-10T07:00')->call('saveBreak')->assertHasErrors(['breakForm.started_at']);
        $this->assertDatabaseMissing('break_logs', ['attendance_log_id' => $log->id]);
    }

    public function test_late_attendance_can_be_reconciled_with_original_status_preserved(): void
    {
        $log = $this->attendance('late');
        Livewire::test(ReconciliationBoard::class)->call('reconcile', $log->id)->set('statusFilter', 'reconciled')->assertSee($this->guard->full_name);
        $this->assertNotNull($log->fresh()->reconciled_at);
        $this->assertSame('late', $log->fresh()->original_status);
    }

    public function test_successful_swap_creates_a_new_assignment_and_confirmation(): void
    {
        $assignment = app(ScheduleService::class)->assignGuard($this->shift, $this->guard);
        $replacement = Guard::create(['tenant_id' => $this->admin->tenant_id, 'first_name' => 'Available', 'last_name' => 'Replacement', 'status' => 'active', 'verification_status' => 'verified']);
        $swap = ShiftSwapRequest::create(['tenant_id' => $this->admin->tenant_id, 'shift_assignment_id' => $assignment->id, 'requested_by_guard_id' => $this->guard->id, 'replacement_guard_id' => $replacement->id, 'status' => 'pending']);
        Livewire::test(ShiftExchangeIndex::class)->call('approveSwap', $swap->id)->assertSee('Shift exchange approved');
        $this->assertSame('cancelled', $assignment->fresh()->status->value);
        $this->assertSame('approved', $swap->fresh()->status->value);
        $this->assertDatabaseHas('shift_assignments', ['shift_id' => $this->shift->id, 'guard_id' => $replacement->id, 'status' => 'assigned']);
        $this->assertDatabaseHas('shift_confirmations', ['guard_id' => $replacement->id, 'status' => 'pending']);
    }

    public function test_embedded_template_apply_returns_to_roster_without_navigation(): void
    {
        $template = ShiftTemplate::create(['tenant_id' => $this->admin->tenant_id, 'name' => 'Audit template', 'is_active' => true]);
        $template->items()->create(['site_id' => $this->shift->site_id, 'day_of_week' => 1, 'start_time' => '18:00', 'end_time' => '06:00', 'required_guards' => 1]);
        Livewire::test(ShiftTemplateIndex::class, ['embedded' => true])
            ->set('applyTemplateId', $template->id)->set('weekStart', '2030-06-10')->call('apply')
            ->assertHasNoErrors()->assertDispatched('scheduler-open-panel', tab: 'roster', date: '2030-06-10')
            ->assertNoRedirect();
        $shift = Shift::where('title', 'Audit template · Mon')->firstOrFail();
        $this->assertSame('2030-06-10 18:00', $shift->starts_at->format('Y-m-d H:i'));
        $this->assertSame('2030-06-11 06:00', $shift->ends_at->format('Y-m-d H:i'));
    }

    public function test_time_off_can_be_submitted_edited_and_approved(): void
    {
        $page = Livewire::test(TimeOffIndex::class)->call('openLeaveForm')
            ->set('leaveForm.guard_id', (string) $this->guard->id)
            ->set('leaveForm.starts_on', '2030-06-12')->set('leaveForm.ends_on', '2030-06-13')
            ->call('submitLeave')->assertHasNoErrors()->assertSet('showLeaveForm', false);
        $leave = LeaveRequest::where('guard_id', $this->guard->id)->firstOrFail();
        $page->call('editLeave', $leave->id)->set('leaveForm.reason', 'Annual leave')->call('submitLeave')->assertHasNoErrors()
            ->call('approveLeave', $leave->id);
        $this->assertSame('approved', $leave->fresh()->status->value);
        $this->assertSame('Annual leave', $leave->fresh()->reason);
    }

    public function test_roster_rejects_a_post_from_another_site_with_inline_feedback(): void
    {
        $otherSite = Site::whereKeyNot($this->shift->site_id)->firstOrFail();
        $post = SitePost::create(['tenant_id' => $this->admin->tenant_id, 'site_id' => $otherSite->id, 'name' => 'Other site post', 'required_guards' => 1, 'status' => 'active']);
        Livewire::test(ScheduleIndex::class)->call('editShift', $this->shift->id)
            ->set('form.site_post_id', (string) $post->id)->call('save')->assertHasErrors(['form.site_post_id']);
        $this->assertNull($this->shift->fresh()->site_post_id);
    }

    public function test_completed_clock_in_requires_break_end_within_shift_window(): void
    {
        $log = $this->attendance('completed');
        $log->update(['clock_out_at' => '2030-06-10 16:00']);
        Livewire::test(AttendanceIndex::class, ['date' => '2030-06-10'])->call('openBreakForm', $log->id)
            ->set('breakForm.started_at', '2030-06-10T12:00')->call('saveBreak')->assertHasErrors(['breakForm.ended_at'])
            ->set('breakForm.ended_at', '2030-06-10T12:30')->call('saveBreak')->assertHasNoErrors();
        $this->assertDatabaseHas('break_logs', ['attendance_log_id' => $log->id]);
    }

    private function attendance(string $status, bool $clockIn = true): AttendanceLog
    {
        $assignment = app(ScheduleService::class)->assignGuard($this->shift, $this->guard);

        return AttendanceLog::create(['shift_assignment_id' => $assignment->id, 'tenant_id' => $this->admin->tenant_id, 'guard_id' => $this->guard->id, 'site_id' => $this->shift->site_id, 'type' => $clockIn ? 'clock_in' : 'no_show', 'recorded_at' => '2030-06-10 08:00', 'clock_in_at' => $clockIn ? '2030-06-10 08:00' : null, 'status' => $status]);
    }
}
