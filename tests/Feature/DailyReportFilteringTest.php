<?php

namespace Tests\Feature;

use App\Livewire\Reports\DailyReportIndex;
use App\Models\DailyActivityReport;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DailyReportFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('email', 'admin@demo.test')->firstOrFail();
        app()->instance('currentTenant', $this->admin->tenant);
        setPermissionsTeamId($this->admin->tenant_id);
    }

    public function test_daily_reports_can_be_filtered_by_site_and_period_and_cleared(): void
    {
        $report = DailyActivityReport::query()
            ->where('tenant_id', $this->admin->tenant_id)
            ->where('title', 'Day shift summary')
            ->firstOrFail();
        $report->update(['report_date' => '2025-03-10']);

        $otherSite = Site::query()
            ->where('tenant_id', $this->admin->tenant_id)
            ->whereKeyNot($report->site_id)
            ->firstOrFail();
        $otherReport = DailyActivityReport::create([
            'tenant_id' => $this->admin->tenant_id,
            'shift_assignment_id' => $report->shift_assignment_id,
            'site_id' => $otherSite->id,
            'guard_id' => $report->guard_id,
            'title' => 'Prior day report',
            'report_date' => '2025-03-09',
            'summary' => 'A separate report for filter verification.',
            'status' => 'submitted',
        ]);

        Livewire::actingAs($this->admin)
            ->test(DailyReportIndex::class)
            ->set('siteFilter', (string) $report->site_id)
            ->set('dateFrom', '2025-03-10')
            ->set('dateTo', '2025-03-10')
            ->assertHasNoErrors()
            ->assertSee($report->title)
            ->assertSee('Site: '.$report->site->name)
            ->assertDontSee($otherReport->title)
            ->assertSee('Report period: Mar 10, 2025 – Mar 10, 2025')
            ->call('clearFilters')
            ->assertSet('siteFilter', '')
            ->assertSet('dateFrom', '')
            ->assertSet('dateTo', '')
            ->assertSee($otherReport->title);
    }


    public function test_daily_report_filters_announce_when_no_results_match(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DailyReportIndex::class)
            ->set('dateFrom', '2100-01-01')
            ->assertSee('No matching reports')
            ->assertSee('Report period: From Jan 1, 2100');
    }

    public function test_daily_report_filters_reject_invalid_dates_and_reversed_periods(): void
    {
        Livewire::actingAs($this->admin)
            ->test(DailyReportIndex::class)
            ->set('dateFrom', 'not-a-date')
            ->assertHasErrors(['dateFrom'])
            ->set('dateFrom', '2025-03-10')
            ->set('dateTo', '2025-03-09')
            ->assertHasErrors(['dateTo'])
            ->set('siteFilter', '999999999')
            ->assertHasErrors(['siteFilter']);
    }
}
