<?php

namespace Tests\Feature;

use App\Livewire\Billing\EstimateForm;
use App\Livewire\Billing\ExpenseIndex;
use App\Livewire\Billing\InvoiceForm;
use App\Livewire\Billing\PayrollBoard;
use App\Models\AttendanceLog;
use App\Models\ClientAccount;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Guard;
use App\Models\Invoice;
use App\Models\PayrollExport;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\Site;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Timesheet;
use App\Models\User;
use App\Services\BillingService;
use App\Services\EstimateService;
use App\Services\ExpenseService;
use App\Services\FileUploadService;
use App\Services\PayrollExportService;
use App\Services\PayrollService;
use App\Services\PlanEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class BackOfficeWorkflowTest extends TestCase
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

    public function test_back_office_plan_routes_cover_create_show_and_edit_pages(): void
    {
        $routes = config('plan_entitlements.routes');

        $this->assertSame('billing', $routes['billing.invoices.create']);
        $this->assertSame('billing', $routes['billing.invoices.show']);
        $this->assertSame('billing', $routes['billing.invoices.edit']);
        $this->assertSame('estimates', $routes['billing.estimates.create']);
        $this->assertSame('estimates', $routes['billing.estimates.show']);
        $this->assertSame('estimates', $routes['billing.estimates.edit']);
    }

    public function test_enterprise_demo_plan_includes_expenses(): void
    {
        $plan = app(SubscriptionPlan::class)::where('slug', 'enterprise')->firstOrFail();

        $this->assertContains('expenses', $plan->features);
    }

    public function test_trial_subscription_usage_limits_follow_the_selected_plan(): void
    {
        $usage = app(PlanEntitlementService::class)->usageSummary($this->admin->tenant_id);
        $plan = app(PlanEntitlementService::class)->planForTenant($this->admin->tenant_id);

        $this->assertSame($plan->max_guards, $usage['guards']['max']);
        $this->assertSame($plan->max_sites, $usage['sites']['max']);
    }

    public function test_back_office_complaints_open_inside_clients(): void
    {
        $complaintsUrl = route('clients.index', ['tab' => 'complaints', 'status' => 'open']);

        $this->actingAs($this->admin)
            ->get(route('billing.hub'))
            ->assertOk()
            ->assertSee($complaintsUrl);

        $this->get($complaintsUrl)
            ->assertOk()
            ->assertSee('Client directory')
            ->assertSee('Log complaint')
            ->assertDontSee('Add client');

        $this->get(route('clients.complaints', ['status' => 'open']))
            ->assertRedirect($complaintsUrl);

        $this->get(route('clients.index'))
            ->assertOk()
            ->assertSee('Add client')
            ->assertDontSee('Log complaint');
    }

    public function test_back_office_hub_exposes_a_clear_cash_flow_hierarchy(): void
    {
        $this->actingAs($this->admin)
            ->get(route('billing.hub'))
            ->assertOk()
            ->assertSee('Operations')
            ->assertSee('Client complaints')
            ->assertSee('Governance')
            ->assertSee('Cash flow')
            ->assertSee('Workspace summary')
            ->assertSee('Recent invoices')
            ->assertSee('Recent expenses');
    }

    public function test_asset_staff_can_open_back_office_without_seeing_finance(): void
    {
        $staff = User::factory()->create(['tenant_id' => $this->admin->tenant_id]);
        $staff->givePermissionTo('equipment.manage');

        $this->actingAs($staff)->get(route('billing.hub'))
            ->assertOk()
            ->assertSee('Operations')
            ->assertSee('Categories low on stock')
            ->assertDontSee('Cash flow')
            ->assertDontSee('Recent invoices');
    }

    public function test_invoice_and_estimate_forms_show_a_single_primary_action_with_live_totals(): void
    {
        Livewire::actingAs($this->admin)
            ->test(InvoiceForm::class)
            ->assertSee('Invoice total')
            ->assertSeeHtml('form="invoice-form"');

        Livewire::actingAs($this->admin)
            ->test(EstimateForm::class)
            ->assertSee('Estimate total')
            ->assertSeeHtml('form="estimate-form"');
    }

    public function test_subscription_workspace_uses_mobile_section_navigation(): void
    {
        $this->actingAs($this->admin)
            ->get(route('billing.subscription'))
            ->assertOk()
            ->assertSee('id="sub-sidebar-select"', false)
            ->assertSee('Your plan');
    }

    public function test_billing_forms_reject_related_records_from_another_tenant(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Billing Tenant',
            'slug' => 'other-billing-tenant',
            'status' => 'active',
        ]);
        $foreignClient = ClientAccount::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Client',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin);
        setPermissionsTeamId($this->admin->tenant_id);

        $invoice = new InvoiceForm;
        $invoice->form['client_account_id'] = $foreignClient->id;

        try {
            $invoice->save(app(BillingService::class));
            $this->fail('Cross-tenant invoice clients should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('form.client_account_id', $exception->errors());
        }

        $estimate = new EstimateForm;
        $estimate->form['client_account_id'] = $foreignClient->id;
        $estimate->form['valid_until'] = today()->addDays(30)->toDateString();

        try {
            $estimate->save(app(EstimateService::class));
            $this->fail('Cross-tenant estimate clients should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('form.client_account_id', $exception->errors());
        }
    }

    public function test_expenses_reject_foreign_allocations(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Expense Tenant',
            'slug' => 'other-expense-tenant',
            'status' => 'active',
        ]);
        $foreignClient = ClientAccount::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Expense Client',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin);
        setPermissionsTeamId($this->admin->tenant_id);
        $component = new ExpenseIndex;
        $component->form = [
            'expense_category_id' => '',
            'client_account_id' => $foreignClient->id,
            'site_id' => '',
            'title' => 'Cross-tenant expense',
            'description' => '',
            'vendor_name' => '',
            'expense_date' => today()->toDateString(),
            'amount' => 50,
            'payment_method' => 'cash',
        ];

        try {
            $component->save(app(ExpenseService::class), app(FileUploadService::class));
            $this->fail('Cross-tenant expense allocations should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('form.client_account_id', $exception->errors());
        }
    }

    public function test_estimate_acceptance_and_conversion_are_guarded_and_idempotent(): void
    {
        $estimate = $this->createEstimate();
        $service = app(EstimateService::class);

        $service->accept($estimate);
        $this->assertSame('accepted', $estimate->fresh()->status);

        $invoiceCount = Invoice::where('tenant_id', $this->admin->tenant_id)->count();
        $invoice = $service->convertToInvoice($estimate->fresh());
        $sameInvoice = $service->convertToInvoice($estimate->fresh());

        $this->assertSame($invoice->id, $sameInvoice->id);
        $this->assertSame($invoice->id, $estimate->fresh()->converted_invoice_id);
        $this->assertSame($invoiceCount + 1, Invoice::where('tenant_id', $this->admin->tenant_id)->count());

        $this->expectException(RuntimeException::class);
        $service->accept($estimate->fresh());
    }

    public function test_draft_invoice_cannot_receive_payment_and_payment_cannot_exceed_balance(): void
    {
        $invoice = $this->createInvoice('draft', 1000);
        $service = app(BillingService::class);

        try {
            $service->recordPayment($invoice, 100);
            $this->fail('Draft invoices should not receive payments.');
        } catch (RuntimeException) {
            $this->assertSame(0.0, (float) $invoice->fresh()->amount_paid);
        }

        $invoice->update(['status' => 'sent']);

        $this->expectException(RuntimeException::class);
        $service->recordPayment($invoice->fresh(), 1001);
    }

    public function test_monthly_invoice_generation_rejects_an_empty_billing_period(): void
    {
        $client = ClientAccount::create([
            'tenant_id' => $this->admin->tenant_id,
            'name' => 'No Billable Rate Client',
            'status' => 'active',
            'default_monthly_rate' => 0,
        ]);

        $this->expectException(RuntimeException::class);
        app(BillingService::class)->generateMonthlyInvoice($client, now()->addYears(2)->format('Y-m'));
    }

    public function test_monthly_invoice_generation_is_idempotent_for_a_client_and_period(): void
    {
        $client = ClientAccount::where('tenant_id', $this->admin->tenant_id)->firstOrFail();
        $month = now()->format('Y-m');

        $first = app(BillingService::class)->generateMonthlyInvoice($client, $month);
        $second = app(BillingService::class)->generateMonthlyInvoice($client, $month);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Invoice::where('tenant_id', $this->admin->tenant_id)
            ->where('client_account_id', $client->id)
            ->whereDate('billing_period', now()->startOfMonth())
            ->count());
    }

    public function test_payment_updates_invoice_totals_atomically(): void
    {
        $invoice = $this->createInvoice('sent', 1000);

        app(BillingService::class)->recordPayment($invoice, 250, 'bank_transfer');
        app(BillingService::class)->recordPayment($invoice->fresh(), 750, 'cash');

        $invoice->refresh();
        $this->assertSame(1000.0, (float) $invoice->amount_paid);
        $this->assertSame('paid', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
        $this->assertSame(2, $invoice->payments()->count());
    }

    public function test_invoice_open_and_overdue_scopes_use_payment_and_due_date_state(): void
    {
        $draft = $this->createInvoice('draft', 500);
        $draft->update(['due_date' => today()->subDay()]);
        $sent = $this->createInvoice('sent', 500);
        $sent->update(['due_date' => today()->subDay()]);

        $this->assertFalse(Invoice::open()->whereKey($draft->id)->exists());
        $this->assertTrue(Invoice::open()->whereKey($sent->id)->exists());
        $this->assertTrue(Invoice::overdue()->whereKey($sent->id)->exists());
    }

    public function test_expense_state_transitions_are_guarded_in_the_service(): void
    {
        $expense = Expense::create([
            'tenant_id' => $this->admin->tenant_id,
            'created_by_user_id' => $this->admin->id,
            'expense_number' => 'EXP-STATE',
            'title' => 'Guarded expense',
            'expense_date' => today(),
            'amount' => 100,
            'status' => 'paid',
        ]);

        $this->expectException(RuntimeException::class);
        app(ExpenseService::class)->approve($expense);
    }

    public function test_payroll_generation_validates_dates_and_reuses_open_timesheet(): void
    {
        $guard = Guard::where('tenant_id', $this->admin->tenant_id)->firstOrFail();
        $service = app(PayrollService::class);

        try {
            $service->generateTimesheet($guard, today()->toDateString(), today()->subDay()->toDateString());
            $this->fail('Invalid payroll ranges should be rejected.');
        } catch (RuntimeException) {
            $this->assertTrue(true);
        }

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();
        $first = $service->generateTimesheet($guard, $start, $end);
        $second = $service->generateTimesheet($guard, $start, $end);

        $this->assertSame($first->id, $second->id);
        $this->assertSame('pending', $second->status);
        $this->assertSame(1, Timesheet::where('guard_id', $guard->id)
            ->whereDate('period_start', $start)
            ->whereDate('period_end', $end)
            ->count());
    }

    public function test_payroll_screen_cannot_generate_for_a_foreign_guard(): void
    {
        $otherTenant = Tenant::create([
            'name' => 'Other Payroll Tenant',
            'slug' => 'other-payroll-tenant',
            'status' => 'active',
        ]);
        $foreignGuard = Guard::withoutGlobalScopes()->create([
            'tenant_id' => $otherTenant->id,
            'first_name' => 'Foreign',
            'last_name' => 'Guard',
            'employee_number' => 'FOREIGN-PAYROLL',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin);
        setPermissionsTeamId($this->admin->tenant_id);
        $component = new PayrollBoard;
        $component->guardId = $foreignGuard->id;
        $component->periodStart = today()->toDateString();
        $component->periodEnd = today()->toDateString();

        try {
            $component->generateTimesheet(app(PayrollService::class));
            $this->fail('Foreign guards should not be accepted for payroll generation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('guardId', $exception->errors());
        }
    }

    public function test_payroll_prorates_each_month_and_rejects_overlapping_periods(): void
    {
        $guard = Guard::forTenant($this->admin->tenant_id)->firstOrFail();
        $guard->update(['monthly_rate' => 3100]);
        $service = app(PayrollService::class);

        $sheet = $service->generateTimesheet($guard, '2026-01-31', '2026-02-01');

        $this->assertSame(210.71, (float) $sheet->gross_pay);
        $this->expectExceptionMessage('This period overlaps a pending or approved timesheet');
        $service->generateTimesheet($guard, '2026-02-01', '2026-02-02');
    }

    public function test_payroll_export_uses_only_approved_timesheets_for_the_period(): void
    {
        Storage::fake('local');
        $guard = Guard::where('tenant_id', $this->admin->tenant_id)->firstOrFail();
        Timesheet::create([
            'tenant_id' => $this->admin->tenant_id,
            'guard_id' => $guard->id,
            'period_start' => today()->startOfMonth(),
            'period_end' => today()->endOfMonth(),
            'regular_hours' => 120,
            'overtime_hours' => 5,
            'gross_pay' => 900,
            'status' => 'approved',
        ]);
        Timesheet::create([
            'tenant_id' => $this->admin->tenant_id,
            'guard_id' => Guard::where('tenant_id', $this->admin->tenant_id)->whereKeyNot($guard->id)->firstOrFail()->id,
            'period_start' => today()->startOfMonth(),
            'period_end' => today()->endOfMonth(),
            'regular_hours' => 80,
            'overtime_hours' => 0,
            'gross_pay' => 700,
            'status' => 'pending',
        ]);

        $export = app(PayrollExportService::class)->exportQuickBooks(
            today()->startOfMonth()->toDateString(),
            today()->endOfMonth()->toDateString(),
            $this->admin->id,
        );

        $csv = Storage::disk('local')->get($export->file_path);
        $this->assertStringContainsString('"120.00","5.00","900.00"', $csv);
        $this->assertStringNotContainsString('"80.00","0.00","700.00"', $csv);
    }

    public function test_payroll_prepares_and_exports_one_thousand_guards_in_one_period(): void
    {
        Storage::fake('local');
        $tenantId = $this->admin->tenant_id;
        $siteId = Site::forTenant($tenantId)->value('id');
        $shift = Shift::create([
            'tenant_id' => $tenantId, 'site_id' => $siteId, 'title' => 'Payroll test shift',
            'starts_at' => '2030-01-03 08:00:00', 'ends_at' => '2030-01-03 16:00:00', 'status' => 'assigned',
        ]);
        foreach (range(1, 1001) as $index) {
            $guard = Guard::create([
                'tenant_id' => $tenantId, 'employee_number' => "PAYROLL-{$index}",
                'first_name' => "Payroll {$index}", 'last_name' => 'Guard',
                'status' => 'active', 'monthly_rate' => $index === 1001 ? 0 : 3100,
            ]);
            $assignment = ShiftAssignment::create([
                'tenant_id' => $tenantId, 'shift_id' => $shift->id, 'guard_id' => $guard->id, 'status' => 'confirmed',
            ]);
            AttendanceLog::create([
                'shift_assignment_id' => $assignment->id,
                'tenant_id' => $tenantId, 'guard_id' => $guard->id, 'site_id' => $siteId,
                'type' => 'clock_in', 'recorded_at' => '2030-01-03 08:00:00',
                'clock_in_at' => '2030-01-03 08:00:00', 'clock_out_at' => '2030-01-03 16:00:00',
                'worked_minutes' => 480, 'status' => 'on_time',
            ]);
        }

        $board = Livewire::actingAs($this->admin)->test(PayrollBoard::class)
            ->set('periodStart', '2030-01-01')->set('periodEnd', '2030-01-31')
            ->call('preparePayroll')->assertHasNoErrors()->assertSet('preparation.created', 1000)
            ->assertSet('preparation.missing_rate', 1)
            ->call('preparePayroll')->assertSet('preparation.created', 0)->assertSet('preparation.existing', 1000)
            ->set('guardSearch', 'PAYROLL-1000')->assertSee('Payroll 1000 Guard')->assertDontSee('Payroll 999 Guard')
            ->set('guardSearch', '')
            ->call('exportQuickBooks')->assertSee('Approve or reject all pending timesheets')
            ->call('approvePeriod')->assertSee('Approved 1000 timesheets')
            ->call('exportQuickBooks')->assertSee('Payroll CSV export generated');

        $path = "payroll/{$tenantId}/quickbooks-2030-01-01-2030-01-31.csv";
        $csv = Storage::disk('local')->get($path);
        $this->assertCount(1001, explode("\n", $csv));
        $this->assertStringContainsString('"Payroll 1000 Guard","2030-01-01","2030-01-31","8.00","0.00","3100.00"', $csv);
        $this->assertStringNotContainsString('Payroll 1001 Guard', $csv);
        $board->call('downloadPayrollExport', PayrollExport::forTenant($tenantId)->where('file_path', $path)->sole()->id)
            ->assertFileDownloaded(basename($path));
    }

    private function createEstimate(): Estimate
    {
        return app(EstimateService::class)->create(
            [
                'client_account_id' => ClientAccount::where('tenant_id', $this->admin->tenant_id)->value('id'),
                'valid_until' => today()->addDays(30)->toDateString(),
            ],
            [['description' => 'Security services', 'quantity' => 1, 'unit_price' => 1000]],
        );
    }

    private function createInvoice(string $status, float $total): Invoice
    {
        return Invoice::create([
            'tenant_id' => $this->admin->tenant_id,
            'client_account_id' => ClientAccount::where('tenant_id', $this->admin->tenant_id)->value('id'),
            'invoice_number' => 'INV-TEST-'.str()->random(6),
            'invoice_date' => today(),
            'due_date' => today()->addDays(14),
            'status' => $status,
            'subtotal' => $total,
            'tax_total' => 0,
            'grand_total' => $total,
            'amount_paid' => 0,
        ]);
    }
}
