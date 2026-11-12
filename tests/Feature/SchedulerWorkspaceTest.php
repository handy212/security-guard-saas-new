<?php

namespace Tests\Feature;

use App\Livewire\Scheduling\SchedulerWorkspace;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SchedulerWorkspaceTest extends TestCase
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

    public function test_all_scheduler_tabs_render_inside_one_workspace(): void
    {
        foreach (SchedulerWorkspace::PANELS as $tab => [$component]) {
            Livewire::withQueryParams(['tab' => $tab])->test(SchedulerWorkspace::class)->assertSet('activeTab', $tab)
                ->assertSee('Scheduler')->assertSeeLivewire($component);
        }
    }

    public function test_old_links_preserve_selected_date(): void
    {
        $this->get(route('schedules.calendar', ['date' => '2030-06-10']))
            ->assertRedirect(route('schedules.index', ['date' => '2030-06-10', 'tab' => 'calendar']));
    }

    public function test_selected_date_is_kept_when_switching_tabs(): void
    {
        Livewire::test(SchedulerWorkspace::class)
            ->call('updateSelectedDate', '2030-06-10')
            ->call('setTab', 'attendance')->assertSet('date', '2030-06-10')
            ->call('setTab', 'roster')->assertSet('date', '2030-06-10');
    }

    public function test_roster_date_changes_notify_the_workspace(): void
    {
        Livewire::test(\App\Livewire\Scheduling\ScheduleIndex::class, ['embedded' => true, 'date' => '2030-06-10'])
            ->call('nextDay')
            ->assertDispatched('scheduler-date-changed', date: '2030-06-11');
    }

    public function test_tabs_enforce_their_own_permissions(): void
    {
        $admin = auth()->user();
        $admin->syncRoles([]);
        $admin->syncPermissions(['schedules.manage']);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Livewire::test(SchedulerWorkspace::class)
            ->call('setTab', 'attendance')->assertForbidden();
    }

    public function test_guard_cannot_access_scheduler_tabs(): void
    {
        $guard = User::where('email', 'john.guard@test')->firstOrFail();
        Livewire::actingAs($guard)->test(SchedulerWorkspace::class)->assertForbidden();
    }
}
