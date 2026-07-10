<?php

namespace Tests\Feature;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Livewire\Patrols\FleetIndex;
use App\Livewire\Patrols\PatrolBoard;
use App\Livewire\Schedules\DeployWizard;
use App\Livewire\Schedules\DeploymentSheet;
use App\Models\FleetVehicle;
use App\Models\Guard;
use App\Models\PatrolRoute;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\Site;
use App\Models\User;
use App\Models\VehiclePatrol;
use App\Services\FleetService;
use App\Services\ScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FleetAndDeployFlowTest extends TestCase
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

    public function test_fleet_vehicle_can_be_created_and_assigned_to_patrol(): void
    {
        $tenantId = $this->admin->tenant_id;
        $site = Site::where('tenant_id', $tenantId)->firstOrFail();
        $guard = Guard::create([
            'tenant_id' => $tenantId,
            'status' => 'active',
            'verification_status' => 'verified',
            'first_name' => 'Fleet',
            'last_name' => 'Driver',
            'employee_number' => 'G-FLEET-1',
        ]);

        Livewire::actingAs($this->admin)
            ->test(FleetIndex::class)
            ->call('openCreate')
            ->set('form.type', VehicleType::MOTOR->value)
            ->set('form.plate_number', 'MTR-99')
            ->set('form.name', 'Bike 1')
            ->set('form.site_id', (string) $site->id)
            ->call('save')
            ->assertHasNoErrors();

        $vehicle = FleetVehicle::where('plate_number', 'MTR-99')->firstOrFail();
        $this->assertSame(VehicleStatus::AVAILABLE, $vehicle->status);

        $route = PatrolRoute::create([
            'tenant_id' => $tenantId,
            'site_id' => $site->id,
            'name' => 'Gate loop',
            'expected_duration_minutes' => 20,
            'status' => 'active',
        ]);

        $shift = app(ScheduleService::class)->createShift([
            'tenant_id' => $tenantId,
            'client_account_id' => $site->client_account_id,
            'site_id' => $site->id,
            'title' => 'Patrol cover',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHours(4),
            'required_guards' => 1,
        ]);
        app(ScheduleService::class)->assignGuard($shift, $guard);

        $component = Livewire::actingAs($this->admin)
            ->test(PatrolBoard::class)
            ->set('assignForm.patrol_route_id', (string) $route->id)
            ->set('assignForm.guard_id', (string) $guard->id)
            ->set('assignForm.vehicle_id', (string) $vehicle->id)
            ->call('assignPatrol');

        $component->assertHasNoErrors();

        $this->assertSame(VehicleStatus::IN_USE, $vehicle->fresh()->status);
        $this->assertDatabaseHas('vehicle_patrols', [
            'vehicle_id' => $vehicle->id,
            'guard_id' => $guard->id,
            'status' => 'active',
        ]);

        $trip = VehiclePatrol::where('vehicle_id', $vehicle->id)->firstOrFail();
        app(FleetService::class)->endTrip($trip, ['end_odometer' => 100]);
        $this->assertSame(VehicleStatus::AVAILABLE, $vehicle->fresh()->status);
    }

    public function test_deploy_wizard_assigns_and_confirms_guard(): void
    {
        $tenantId = $this->admin->tenant_id;
        $site = Site::where('tenant_id', $tenantId)->firstOrFail();
        $guard = Guard::create([
            'tenant_id' => $tenantId,
            'status' => 'active',
            'verification_status' => 'verified',
            'first_name' => 'Deploy',
            'last_name' => 'Officer',
            'employee_number' => 'G-DEP-1',
        ]);

        $starts = now()->addDays(2)->setTime(8, 0);
        $ends = now()->addDays(2)->setTime(16, 0);

        Livewire::actingAs($this->admin)
            ->test(DeployWizard::class)
            ->set('date', $starts->toDateString())
            ->set('client_account_id', (string) $site->client_account_id)
            ->set('site_id', (string) $site->id)
            ->call('nextStep')
            ->assertSet('step', 2)
            ->set('shift_mode', 'new')
            ->set('title', 'Lobby day')
            ->set('starts_at', $starts->format('Y-m-d\TH:i'))
            ->set('ends_at', $ends->format('Y-m-d\TH:i'))
            ->call('nextStep')
            ->assertSet('step', 3)
            ->set('guard_id', (string) $guard->id)
            ->call('nextStep')
            ->assertSet('step', 4)
            ->set('confirm_now', true)
            ->call('deploy')
            ->assertHasNoErrors()
            ->assertSet('step', 5);

        $this->assertDatabaseHas('shift_assignments', [
            'guard_id' => $guard->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_deployment_sheet_can_confirm_assignment(): void
    {
        $tenantId = $this->admin->tenant_id;
        $site = Site::where('tenant_id', $tenantId)->firstOrFail();
        $guard = Guard::create([
            'tenant_id' => $tenantId,
            'status' => 'active',
            'verification_status' => 'verified',
            'first_name' => 'Sheet',
            'last_name' => 'Guard',
            'employee_number' => 'G-SHEET-1',
        ]);

        $starts = now()->addDays(3)->setTime(9, 0);
        $ends = now()->addDays(3)->setTime(17, 0);

        Livewire::actingAs($this->admin)
            ->test(DeployWizard::class)
            ->set('date', $starts->toDateString())
            ->set('client_account_id', (string) $site->client_account_id)
            ->set('site_id', (string) $site->id)
            ->set('shift_mode', 'new')
            ->set('title', 'Gate')
            ->set('starts_at', $starts->format('Y-m-d\TH:i'))
            ->set('ends_at', $ends->format('Y-m-d\TH:i'))
            ->set('guard_id', (string) $guard->id)
            ->set('confirm_now', false)
            ->set('step', 4)
            ->call('deploy')
            ->assertSet('step', 5);

        $assignment = ShiftAssignment::where('guard_id', $guard->id)->latest('id')->firstOrFail();
        $this->assertSame('assigned', $assignment->status->value);

        Livewire::actingAs($this->admin)
            ->test(DeploymentSheet::class, ['date' => $starts->toDateString()])
            ->call('confirmAssignment', $assignment->id)
            ->assertHasNoErrors();

        $this->assertSame('confirmed', $assignment->fresh()->status->value);
    }
}
