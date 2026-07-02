<?php

namespace Tests\Feature;

use App\Models\ClientAccount;
use App\Models\Guard;
use App\Models\ReportTemplateField;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CustomReportService;
use App\Services\EstimateService;
use App\Services\GeofenceAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompetitiveRoadmapFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@demo.test')->first();
        $this->tenant = $this->admin->tenant;
        app()->instance('currentTenant', $this->tenant);
    }

    public function test_tracking_route_is_accessible(): void
    {
        $this->actingAs($this->admin)
            ->get('/tracking')
            ->assertOk();
    }

    public function test_custom_report_template_can_be_created(): void
    {
        $this->actingAs($this->admin);

        $service = app(CustomReportService::class);
        $template = $service->createTemplate([
            'name' => 'Site Inspection',
            'description' => 'Daily inspection',
            'is_active' => true,
        ], [
            ['label' => 'Notes', 'field_type' => 'textarea', 'is_required' => true],
        ]);

        $this->assertDatabaseHas('report_templates', ['name' => 'Site Inspection']);
        $this->assertSame(1, ReportTemplateField::where('report_template_id', $template->id)->count());
    }

    public function test_geofence_violation_is_recorded(): void
    {
        $site = Site::where('tenant_id', $this->tenant->id)->first();
        $guard = Guard::where('tenant_id', $this->tenant->id)->first();

        $service = app(GeofenceAlertService::class);
        $violation = $service->recordViolation($this->tenant->id, $guard->id, $site, 7.0, -2.0);

        $this->assertDatabaseHas('geofence_violations', ['id' => $violation->id]);
    }

    public function test_estimate_converts_to_invoice(): void
    {
        $this->actingAs($this->admin);

        $client = ClientAccount::where('tenant_id', $this->tenant->id)->first();

        $estimate = app(EstimateService::class)->create(
            ['client_account_id' => $client->id, 'valid_until' => now()->addDays(30)->toDateString()],
            [['description' => 'Security services', 'quantity' => 10, 'unit_price' => 25]],
        );

        app(EstimateService::class)->accept($estimate);
        $invoice = app(EstimateService::class)->convertToInvoice($estimate->fresh());

        $this->assertNotNull($invoice->id);
        $this->assertSame('converted', $estimate->fresh()->status);
    }

    public function test_new_module_routes_exist(): void
    {
        $routes = [
            '/reports/templates',
            '/schedules/templates',
            '/schedules/time-off',
            '/schedules/open-shifts',
            '/messenger',
            '/passdown',
            '/billing/estimates',
            '/attendance/reconciliation',
        ];

        foreach ($routes as $route) {
            $this->actingAs($this->admin)->get($route)->assertOk();
        }
    }

    public function test_idle_guard_detection_command_runs(): void
    {
        $this->artisan('guardops:detect-idle-guards')->assertSuccessful();
    }

    public function test_deliver_client_reports_command_runs(): void
    {
        $this->artisan('guardops:deliver-client-reports')->assertSuccessful();
    }
}
