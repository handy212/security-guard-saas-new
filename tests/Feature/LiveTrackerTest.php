<?php

namespace Tests\Feature;

use App\Livewire\Tracking\LiveTracker;
use App\Models\AttendanceLog;
use App\Models\DispatchEvent;
use App\Models\Guard;
use App\Models\GuardIdleAlert;
use App\Models\GuardLocation;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use App\Services\SiteProtectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LiveTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_live_tracker_page_renders(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        app()->instance('currentTenant', $admin->tenant);

        $this->actingAs($admin)
            ->get(route('tracking.live'))
            ->assertOk()
            ->assertSee('Live Tracker')
            ->assertSee('Open dispatch');
    }

    public function test_active_patriot_alarms_appear_even_when_site_filters_exclude_them(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        app()->instance('currentTenant', $admin->tenant);
        setPermissionsTeamId($admin->tenant_id);
        $alarm = DispatchEvent::create([
            'dispatch_number' => 'PAT-MAP-ACTIVE', 'patriot_job_id' => 701, 'priority' => 'critical',
            'status' => 'open', 'event_type' => 'alarm', 'latitude' => 5.6, 'longitude' => -0.18,
        ]);
        DispatchEvent::create([
            'dispatch_number' => 'PAT-NO-LOCATION', 'patriot_job_id' => 702, 'status' => 'open', 'event_type' => 'alarm',
        ]);
        DispatchEvent::create([
            'dispatch_number' => 'PAT-CLOSED', 'patriot_job_id' => 703, 'status' => 'closed', 'event_type' => 'alarm',
        ]);

        Livewire::actingAs($admin)->test(LiveTracker::class)
            ->set('search', 'no matching site')
            ->set('showGuardMarkers', false)
            ->assertSee('"alarmId":'.$alarm->id.',', false)
            ->assertDontSee('PAT-NO-LOCATION')
            ->assertDontSee('active Patriot alarms')
            ->assertDontSee('Enable alarm sound')
            ->assertDontSee('Locate alarm')
            ->assertDontSee('PAT-CLOSED');
    }

    public function test_dispatch_link_pins_the_requested_location(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        app()->instance('currentTenant', $admin->tenant);
        $dispatch = DispatchEvent::create(['dispatch_number' => 'PAT-MAP-TEST', 'event_type' => 'alarm']);

        $this->actingAs($admin)->get(route('tracking.live', [
            'lat' => 5.6, 'lng' => -0.18, 'dispatch' => $dispatch->id,
        ]))->assertOk()
            ->assertSee('"lat":5.6,"lng":-0.18,"label":"PAT-MAP-TEST"', false)
            ->assertSee('"fitBounds":false', false);
    }

    public function test_current_coverage_uses_verified_attendance_and_detects_outside_and_stale_guards(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        $tenantId = $admin->tenant_id;
        app()->instance('currentTenant', $admin->tenant);

        $site = Site::create([
            'tenant_id' => $tenantId, 'client_account_id' => Site::forTenant($tenantId)->value('client_account_id'),
            'name' => 'Coverage test site', 'latitude' => 5.6, 'longitude' => -0.18, 'status' => 'active',
        ]);
        $guard = Guard::create([
            'tenant_id' => $tenantId, 'employee_number' => 'COVERAGE-TEST',
            'first_name' => 'Coverage', 'last_name' => 'Guard', 'status' => 'active',
        ]);
        $shift = Shift::create([
            'tenant_id' => $tenantId, 'site_id' => $site->id, 'title' => 'Coverage shift',
            'starts_at' => now()->subHour(), 'ends_at' => now()->addHour(), 'required_guards' => 2, 'status' => 'assigned',
        ]);
        $assignment = ShiftAssignment::create([
            'tenant_id' => $tenantId, 'shift_id' => $shift->id, 'guard_id' => $guard->id, 'status' => 'confirmed',
        ]);

        $snapshot = fn () => app(SiteProtectionService::class)->snapshot($tenantId)['sites']->get($site->id);
        $this->assertSame('unprotected', $snapshot()['status']);

        AttendanceLog::create([
            'tenant_id' => $tenantId, 'shift_assignment_id' => $assignment->id, 'guard_id' => $guard->id,
            'site_id' => $site->id, 'type' => 'clock_in', 'recorded_at' => now()->subMinutes(5),
            'clock_in_at' => now()->subMinutes(5), 'geofence_validated' => true, 'status' => 'on_time',
        ]);
        $position = GuardLocation::create([
            'tenant_id' => $tenantId, 'guard_id' => $guard->id,
            'latitude' => 5.6, 'longitude' => -0.18, 'recorded_at' => now(),
        ]);

        $this->assertSame('understaffed', $snapshot()['status']);
        $otherGuard = Guard::create([
            'tenant_id' => $tenantId, 'employee_number' => 'OTHER-COVERAGE-TEST',
            'first_name' => 'Other', 'last_name' => 'Responder', 'status' => 'active',
        ]);
        $otherAssignment = ShiftAssignment::create([
            'tenant_id' => $tenantId, 'shift_id' => $shift->id, 'guard_id' => $otherGuard->id, 'status' => 'confirmed',
        ]);

        Livewire::actingAs($admin)->test(LiveTracker::class)
            ->set('search', 'coverage guard')
            ->set('coverageFilter', 'understaffed')
            ->assertDontSee('tracking-deployment-'.$otherAssignment->id, false)
            ->call('openSite', $site->id)
            ->assertSee('Selected site details', false)
            ->assertSee('1 of 2 required positions covered')
            ->call('openDeployment', $assignment->id)
            ->assertSee('Selected guard details', false)
            ->assertSee('COVERAGE-TEST')
            ->set('coverageFilter', 'protected')
            ->assertSee('No sites match these filters.');
        $shift->update(['required_guards' => 1]);
        $this->assertSame('protected', $snapshot()['status']);

        $position->update(['recorded_at' => now()->subHours(2)]);
        $this->assertSame('verification_required', $snapshot()['status']);
        $this->assertSame(1, $snapshot()['verified']);

        $position->update(['recorded_at' => now(), 'latitude' => 6.6]);
        $this->assertSame('unprotected', $snapshot()['status']);
        $this->assertSame('outside', $snapshot()['deployments']->first()['geofence']);

        $shift->update(['ends_at' => now()->subMinute()]);
        $this->assertSame('not_scheduled', $snapshot()['status']);
    }

    public function test_selecting_a_site_without_coordinates_clears_previous_map_focus(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        app()->instance('currentTenant', $admin->tenant);
        setPermissionsTeamId($admin->tenant_id);
        $site = Site::forTenant($admin->tenant_id)->firstOrFail();
        $site->update(['latitude' => null, 'longitude' => null]);

        Livewire::actingAs($admin)->test(LiveTracker::class)
            ->call('focusCoords', 5.6, -0.18)
            ->call('openSite', $site->id)
            ->assertSet('focusLat', null)
            ->assertSet('focusLng', null)
            ->assertSee('Selected site details', false);
    }

    public function test_idle_alert_list_only_shows_latest_unresolved_alert_per_guard(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        app()->instance('currentTenant', $admin->tenant);
        setPermissionsTeamId($admin->tenant_id);
        $guard = Guard::forTenant($admin->tenant_id)->firstOrFail();
        $base = ['tenant_id' => $admin->tenant_id, 'guard_id' => $guard->id, 'last_location_at' => now()->subHours(2)];
        $old = GuardIdleAlert::create($base + ['idle_minutes' => 60, 'alerted_at' => now()->subHour()]);
        $latest = GuardIdleAlert::create($base + ['idle_minutes' => 120, 'alerted_at' => now()]);

        Livewire::actingAs($admin)->test(LiveTracker::class)
            ->set('panelFilter', 'idle')
            ->assertSee('wire:key="idle-'.$latest->id.'"', false)
            ->assertDontSee('wire:key="idle-'.$old->id.'"', false)
            ->assertSee('No GPS update for 2h');
    }

    public function test_live_tracker_can_filter_by_site(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        $tenant = Tenant::findOrFail($admin->tenant_id);
        app()->instance('currentTenant', $tenant);
        setPermissionsTeamId($tenant->id);

        $site = Site::where('tenant_id', $tenant->id)->firstOrFail();

        Livewire::actingAs($admin)
            ->test(LiveTracker::class)
            ->set('siteFilter', $site->id)
            ->assertSet('siteFilter', $site->id)
            ->assertSee('Live guards');
    }
}
