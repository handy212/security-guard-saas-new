<?php

namespace Tests\Feature;

use App\Models\CheckpointScan;
use App\Models\ClientAccount;
use App\Models\ClientReportApproval;
use App\Models\DailyActivityReport;
use App\Models\PatrolSession;
use App\Models\Tenant;
use App\Models\TenantSetting;
use App\Models\User;
use App\Services\TenantBrandingService;
use App\Services\TenantRoleProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPortalDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private ClientAccount $client;

    private User $portalUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->tenant = User::where('email', 'admin@demo.test')->first()->tenant;
        app()->instance('currentTenant', $this->tenant);

        $this->client = ClientAccount::where('tenant_id', $this->tenant->id)->firstOrFail();

        $this->portalUser = User::create([
            'tenant_id' => $this->tenant->id,
            'client_account_id' => $this->client->id,
            'name' => 'Portal Reviewer',
            'email' => 'portal-reviewer@test.test',
            'password' => 'password',
            'status' => 'active',
        ]);
        app(TenantRoleProvisioner::class)->assignRole($this->portalUser, 'client');
    }

    private function makeApproval(ClientAccount $client): ClientReportApproval
    {
        $report = DailyActivityReport::where('tenant_id', $this->tenant->id)->firstOrFail();

        return ClientReportApproval::create([
            'tenant_id' => $this->tenant->id,
            'client_account_id' => $client->id,
            'approvable_type' => DailyActivityReport::class,
            'approvable_id' => $report->id,
            'status' => 'pending',
        ]);
    }

    public function test_pending_decision_appears_before_routine_metrics(): void
    {
        $approval = $this->makeApproval($this->client);

        $this->actingAs($this->portalUser)
            ->get(route('client-portal.dashboard'))
            ->assertOk()
            ->assertSeeInOrder([
                'Reports waiting for your decision',
                'Daily Activity Report #'.$approval->approvable_id,
                'Recent shifts',
            ]);
    }

    public function test_client_portal_uses_the_shared_organization_palette(): void
    {
        TenantSetting::create([
            'tenant_id' => $this->tenant->id,
            'key' => TenantBrandingService::THEME_KEY,
            'value' => ['light' => '#b91c1c', 'dark' => '#ef4444'],
        ]);

        $this->actingAs($this->portalUser)
            ->get(route('client-portal.dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['<body', '--tenant-accent: #b91c1c;', '--tenant-dark-accent: #ef4444;'], escape: false);
    }

    public function test_dashboard_reports_when_no_decisions_are_waiting(): void
    {
        ClientReportApproval::where('tenant_id', $this->tenant->id)->delete();

        $this->actingAs($this->portalUser)
            ->get(route('client-portal.dashboard'))
            ->assertOk()
            ->assertSee('No reports are waiting for your approval.')
            ->assertDontSee('Reports waiting for your decision');
    }

    public function test_other_client_pending_decisions_stay_hidden(): void
    {
        $otherClient = ClientAccount::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Other Client Co',
            'status' => 'active',
        ]);
        $foreignApproval = $this->makeApproval($otherClient);

        $this->actingAs($this->portalUser)
            ->get(route('client-portal.dashboard'))
            ->assertOk()
            ->assertDontSee('Daily Activity Report #'.$foreignApproval->approvable_id);
    }

    public function test_approved_daily_report_shows_shift_patrol_evidence(): void
    {
        $report = DailyActivityReport::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('title', 'Day shift summary')
            ->firstOrFail();
        $report->update(['status' => 'approved']);

        $scan = CheckpointScan::query()
            ->where('tenant_id', $this->tenant->id)
            ->whereHas('session', fn ($query) => $query
                ->where('shift_assignment_id', $report->shift_assignment_id)
                ->where('status', 'completed'))
            ->with('checkpoint')
            ->firstOrFail();

        $this->actingAs($this->portalUser)
            ->get(route('client-portal.dashboard'))
            ->assertOk()
            ->assertSee(route('client-portal.reports.show', $report->id));

        $this->actingAs($this->portalUser)
            ->get(route('client-portal.reports.show', $report->id))
            ->assertOk()
            ->assertSee($report->title)
            ->assertSee($report->site->name)
            ->assertSee('Service period')
            ->assertSee($scan->checkpoint->name);
    }

    public function test_client_cannot_view_an_approved_report_for_another_client(): void
    {
        $report = DailyActivityReport::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('title', 'Day shift summary')
            ->firstOrFail();
        $report->update(['status' => 'approved']);

        $otherClient = ClientAccount::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Other Report Client',
            'status' => 'active',
        ]);
        $report->site()->update(['client_account_id' => $otherClient->id]);

        $this->actingAs($this->portalUser)
            ->get(route('client-portal.reports.show', $report->id))
            ->assertNotFound();
    }

    public function test_approved_report_without_patrols_shows_an_empty_evidence_state(): void
    {
        $report = DailyActivityReport::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('title', 'Day shift summary')
            ->firstOrFail();
        $report->update(['status' => 'approved']);
        PatrolSession::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('shift_assignment_id', $report->shift_assignment_id)
            ->delete();

        $this->actingAs($this->portalUser)
            ->get(route('client-portal.reports.show', $report->id))
            ->assertOk()
            ->assertSee('No patrol evidence linked')
            ->assertSee('No patrol sessions were recorded for this shift.');
    }
}
