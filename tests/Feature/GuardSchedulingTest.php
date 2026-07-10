<?php

namespace Tests\Feature;

use App\Livewire\Guard\MobileDashboard;
use App\Livewire\Scheduling\OpenShiftsIndex;
use App\Livewire\Scheduling\ScheduleIndex;
use App\Livewire\Scheduling\ShiftExchangeIndex;
use App\Livewire\Scheduling\ShiftTemplateIndex;
use App\Livewire\Scheduling\TimeOffIndex;
use App\Models\ClientAccount;
use App\Models\Guard;
use App\Models\LeaveRequest;
use App\Models\OpenShiftBid;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftConfirmation;
use App\Models\ShiftSwapRequest;
use App\Models\ShiftTemplate;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use App\Services\EnterpriseScheduleService;
use App\Services\ScheduleService;
use App\Services\WorkforceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

class GuardSchedulingTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $guardUser;

    private Guard $guard;

    private Tenant $tenant;

    private ClientAccount $client;

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->tenant = Tenant::first();
        $this->admin = User::where('email', 'admin@demo.test')->first();
        $this->guardUser = User::where('email', 'john.guard@test')->first();
        $this->guard = Guard::where('employee_number', 'G-001')->first();
        $this->client = ClientAccount::first();
        $this->site = Site::first();

        app()->instance('currentTenant', $this->tenant);
    }

    public function test_guard_can_bid_on_open_shift_via_api(): void
    {
        $shift = $this->createOpenShift(requiredGuards: 2);

        Sanctum::actingAs($this->guardUser);

        $this->getJson('/api/v1/open-shifts')
            ->assertOk()
            ->assertJsonFragment(['title' => $shift->title]);

        $this->postJson("/api/v1/open-shifts/{$shift->id}/bid", ['notes' => 'Available all day'])
            ->assertCreated();

        $this->assertDatabaseHas('open_shift_bids', [
            'shift_id' => $shift->id,
            'guard_id' => $this->guard->id,
            'status' => 'pending',
        ]);
    }

    public function test_guard_cannot_bid_when_shift_is_fully_staffed(): void
    {
        $shift = $this->createOpenShift(requiredGuards: 1);

        $other = Guard::create([
            'tenant_id' => $this->tenant->id,
            'employee_number' => 'G-BID-1',
            'first_name' => 'Filled',
            'last_name' => 'Guard',
            'status' => 'active',
            'verification_status' => 'verified',
        ]);

        ShiftAssignment::create([
            'tenant_id' => $this->tenant->id,
            'shift_id' => $shift->id,
            'guard_id' => $other->id,
            'status' => 'assigned',
        ]);

        Sanctum::actingAs($this->guardUser);

        $this->postJson("/api/v1/open-shifts/{$shift->id}/bid")
            ->assertUnprocessable();
    }

    public function test_guard_can_request_shift_swap_via_api(): void
    {
        $assignment = ShiftAssignment::where('guard_id', $this->guard->id)->first();

        Sanctum::actingAs($this->guardUser);

        $this->postJson('/api/v1/shift-swaps', [
            'shift_assignment_id' => $assignment->id,
            'reason' => 'Family emergency',
        ])->assertCreated();

        $this->assertDatabaseHas('shift_swap_requests', [
            'shift_assignment_id' => $assignment->id,
            'requested_by_guard_id' => $this->guard->id,
            'status' => 'pending',
        ]);

        $this->getJson('/api/v1/shift-swaps')
            ->assertOk()
            ->assertJsonFragment(['status' => 'pending']);
    }

    public function test_guard_cannot_request_swap_for_another_guards_assignment(): void
    {
        $other = Guard::create([
            'tenant_id' => $this->tenant->id,
            'employee_number' => 'G-SWAP-1',
            'first_name' => 'Other',
            'last_name' => 'Guard',
            'status' => 'active',
            'verification_status' => 'verified',
        ]);

        $shift = $this->createOpenShift();
        $assignment = ShiftAssignment::create([
            'tenant_id' => $this->tenant->id,
            'shift_id' => $shift->id,
            'guard_id' => $other->id,
            'status' => 'assigned',
        ]);

        Sanctum::actingAs($this->guardUser);

        $this->postJson('/api/v1/shift-swaps', [
            'shift_assignment_id' => $assignment->id,
        ])->assertNotFound();
    }

    public function test_admin_approving_bid_assigns_guard_and_queues_confirmation(): void
    {
        $shift = $this->createOpenShift(requiredGuards: 2);
        $bid = OpenShiftBid::create([
            'tenant_id' => $this->tenant->id,
            'shift_id' => $shift->id,
            'guard_id' => $this->guard->id,
            'status' => 'pending',
        ]);

        app(EnterpriseScheduleService::class)->approveBid($bid);

        $assignment = ShiftAssignment::where('shift_id', $shift->id)
            ->where('guard_id', $this->guard->id)
            ->where('status', 'assigned')
            ->first();

        $this->assertNotNull($assignment);
        $this->assertDatabaseHas('shift_confirmations', [
            'shift_assignment_id' => $assignment->id,
            'guard_id' => $this->guard->id,
            'status' => 'pending',
        ]);
    }

    public function test_assign_guard_creates_pending_confirmation(): void
    {
        $shift = $this->createOpenShift(requiredGuards: 2);

        $replacement = Guard::create([
            'tenant_id' => $this->tenant->id,
            'employee_number' => 'G-CONF-1',
            'first_name' => 'Confirm',
            'last_name' => 'Guard',
            'status' => 'active',
            'verification_status' => 'verified',
        ]);

        app(ScheduleService::class)->assignGuard($shift, $replacement);

        $this->assertDatabaseHas('shift_confirmations', [
            'guard_id' => $replacement->id,
            'status' => 'pending',
        ]);
    }

    public function test_template_apply_places_shifts_on_correct_weekdays(): void
    {
        $template = ShiftTemplate::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Week pattern',
            'is_active' => true,
        ]);

        $template->items()->create([
            'day_of_week' => 1,
            'start_time' => '08:00',
            'end_time' => '16:00',
            'site_id' => $this->site->id,
            'required_guards' => 1,
        ]);

        $weekStart = Carbon::parse('2026-07-05'); // Sunday
        $count = app(WorkforceService::class)->applyTemplate($template, $weekStart);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('shifts', [
            'title' => 'Week pattern · Mon',
        ]);

        $shift = Shift::where('title', 'Week pattern · Mon')->first();
        $this->assertTrue($shift->starts_at->isMonday());
    }

    public function test_mobile_dashboard_can_bid_and_request_swap(): void
    {
        $openShift = $this->createOpenShift(requiredGuards: 2);
        $assignment = ShiftAssignment::where('guard_id', $this->guard->id)->first();

        Livewire::actingAs($this->guardUser)
            ->test(MobileDashboard::class)
            ->set('activeAssignmentId', $assignment->id)
            ->call('bidOnShift', $openShift->id)
            ->assertHasNoErrors()
            ->call('requestShiftSwap')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('open_shift_bids', [
            'shift_id' => $openShift->id,
            'guard_id' => $this->guard->id,
        ]);

        $this->assertDatabaseHas('shift_swap_requests', [
            'shift_assignment_id' => $assignment->id,
            'requested_by_guard_id' => $this->guard->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_open_shift_and_exchange_pages_show_pending_items(): void
    {
        $shift = $this->createOpenShift(requiredGuards: 2);
        $assignment = ShiftAssignment::where('guard_id', $this->guard->id)->first();

        OpenShiftBid::create([
            'tenant_id' => $this->tenant->id,
            'shift_id' => $shift->id,
            'guard_id' => $this->guard->id,
            'status' => 'pending',
        ]);

        ShiftSwapRequest::create([
            'tenant_id' => $this->tenant->id,
            'shift_assignment_id' => $assignment->id,
            'requested_by_guard_id' => $this->guard->id,
            'status' => 'pending',
        ]);

        Livewire::actingAs($this->admin)
            ->test(OpenShiftsIndex::class)
            ->assertSee('Guard bids');

        Livewire::actingAs($this->admin)
            ->test(ShiftExchangeIndex::class)
            ->assertSee($this->guard->full_name);
    }

    public function test_schedule_hub_includes_calendar_and_deployment(): void
    {
        foreach (['/schedules/calendar', '/schedules/deployment-sheet'] as $route) {
            $this->actingAs($this->admin)->get($route)->assertOk();
        }
    }

    public function test_shift_template_save_persists_times(): void
    {
        Livewire::actingAs($this->admin)
            ->test(ShiftTemplateIndex::class)
            ->set('showForm', true)
            ->set('form.name', 'Night cover')
            ->set('items.0.site_id', (string) $this->site->id)
            ->set('items.0.day_of_week', 3)
            ->set('items.0.start_time', '20:00')
            ->set('items.0.end_time', '06:00')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('shift_template_items', [
            'site_id' => $this->site->id,
            'day_of_week' => 3,
            'start_time' => '20:00',
            'end_time' => '06:00',
        ]);
    }

    public function test_confirm_shift_updates_assignment_status(): void
    {
        $shift = $this->createOpenShift();
        $replacement = Guard::create([
            'tenant_id' => $this->tenant->id,
            'employee_number' => 'G-CONF-2',
            'first_name' => 'Ready',
            'last_name' => 'Guard',
            'status' => 'active',
            'verification_status' => 'verified',
        ]);

        $assignment = app(ScheduleService::class)->assignGuard($shift, $replacement);
        $confirmation = ShiftConfirmation::where('shift_assignment_id', $assignment->id)->first();

        app(WorkforceService::class)->confirmShift($confirmation);

        $this->assertDatabaseHas('shift_assignments', [
            'id' => $assignment->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_shift_template_can_be_deactivated(): void
    {
        $template = ShiftTemplate::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Toggle me',
            'is_active' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test(ShiftTemplateIndex::class)
            ->call('toggleActive', $template->id);

        $this->assertFalse($template->fresh()->is_active);
    }

    public function test_pending_leave_can_be_cancelled(): void
    {
        $leave = LeaveRequest::create([
            'tenant_id' => $this->tenant->id,
            'guard_id' => $this->guard->id,
            'type' => 'annual',
            'starts_on' => now()->addWeek(),
            'ends_on' => now()->addWeeks(2),
            'status' => 'pending',
        ]);

        Livewire::actingAs($this->admin)
            ->test(TimeOffIndex::class)
            ->call('cancelLeave', $leave->id);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_cannot_assign_guard_with_approved_leave(): void
    {
        $shift = $this->createOpenShift();

        LeaveRequest::create([
            'tenant_id' => $this->tenant->id,
            'guard_id' => $this->guard->id,
            'type' => 'annual',
            'starts_on' => $shift->starts_at->copy()->subDay()->toDateString(),
            'ends_on' => $shift->ends_at->copy()->addDay()->toDateString(),
            'status' => 'approved',
        ]);

        $this->expectException(\RuntimeException::class);
        app(ScheduleService::class)->assignGuard($shift, $this->guard);
    }

    public function test_cannot_approve_leave_when_shifts_overlap(): void
    {
        $shift = $this->createOpenShift();
        ShiftAssignment::create([
            'tenant_id' => $this->tenant->id,
            'shift_id' => $shift->id,
            'guard_id' => $this->guard->id,
            'status' => 'assigned',
        ]);

        $leave = LeaveRequest::create([
            'tenant_id' => $this->tenant->id,
            'guard_id' => $this->guard->id,
            'type' => 'annual',
            'starts_on' => $shift->starts_at->toDateString(),
            'ends_on' => $shift->ends_at->toDateString(),
            'status' => 'pending',
        ]);

        Livewire::actingAs($this->admin)
            ->test(TimeOffIndex::class)
            ->call('approveLeave', $leave->id);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leave->id,
            'status' => 'pending',
        ]);
    }

    public function test_cannot_assign_guard_outside_availability_window(): void
    {
        $shift = $this->createOpenShift();

        \App\Models\GuardAvailability::create([
            'tenant_id' => $this->tenant->id,
            'guard_id' => $this->guard->id,
            'weekday' => $shift->starts_at->dayOfWeek,
            'starts_at' => '06:00',
            'ends_at' => '07:00',
            'is_available' => true,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not available');
        app(ScheduleService::class)->assignGuard($shift, $this->guard);
    }

    public function test_clock_in_and_out_advance_shift_status(): void
    {
        $starts = now()->next(Carbon::MONDAY)->setTime(8, 0);
        $shift = Shift::create([
            'tenant_id' => $this->tenant->id,
            'client_account_id' => $this->client->id,
            'site_id' => $this->site->id,
            'title' => 'Status flow shift',
            'starts_at' => $starts,
            'ends_at' => $starts->copy()->setTime(16, 0),
            'required_guards' => 1,
            'status' => 'open',
        ]);

        $assignment = app(ScheduleService::class)->assignGuard($shift, $this->guard);
        $attendance = app(\App\Services\AttendanceService::class);

        $attendance->clockIn($assignment->id, 5.6, -0.2, enforceGeofence: false);
        $this->assertSame('in_progress', $shift->fresh()->status->value);

        $log = \App\Models\AttendanceLog::where('shift_assignment_id', $assignment->id)->first();
        $attendance->clockOut($log->id, 5.6, -0.2);
        $this->assertSame('completed', $shift->fresh()->status->value);
    }

    public function test_time_off_page_renders_filters_and_table(): void
    {
        Livewire::actingAs($this->admin)
            ->test(TimeOffIndex::class)
            ->assertSee('Leave requests')
            ->assertSee('Conflicts')
            ->assertSee('Pending');
    }

    private function createOpenShift(int $requiredGuards = 1): Shift
    {
        return Shift::create([
            'tenant_id' => $this->tenant->id,
            'client_account_id' => $this->client->id,
            'site_id' => $this->site->id,
            'title' => 'Open shift '.uniqid(),
            'starts_at' => now()->addDays(2)->setTime(8, 0),
            'ends_at' => now()->addDays(2)->setTime(16, 0),
            'required_guards' => $requiredGuards,
            'status' => 'open',
        ]);
    }
}
