<?php

namespace Tests\Feature;

use App\Livewire\Billing\EstimateForm;
use App\Livewire\Billing\ExpenseIndex;
use App\Livewire\Billing\InvoiceForm;
use App\Livewire\Billing\PayrollBoard;
use App\Models\ClientAccount;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Guard;
use App\Models\Invoice;
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

    public function test_back_office_hub_exposes_a_clear_cash_flow_hierarchy(): void
    {
        $this->actingAs($this->admin)
            ->get(route('billing.hub'))
            ->assertOk()
            ->assertSee('Cash flow')
            ->assertSee('Workspace summary')
            ->assertSee('Recent invoices')
            ->assertSee('Recent expenses');
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
