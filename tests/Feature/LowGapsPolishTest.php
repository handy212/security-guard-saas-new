<?php

namespace Tests\Feature;

use App\Enums\DispatchPriority;
use App\Enums\DispatchStatus;
use App\Livewire\Analytics\AnalyticsDashboard;
use App\Livewire\Billing\InvoiceIndex;
use App\Livewire\Dispatch\DispatcherBoard;
use App\Models\AttendanceLog;
use App\Models\ClientAccount;
use App\Models\DispatchEvent;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ShiftAssignment;
use App\Models\Site;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\BillingService;
use App\Services\DispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LowGapsPolishTest extends TestCase
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

    public function test_dispatch_promotes_to_incident(): void
    {
        $site = Site::where('tenant_id', $this->admin->tenant_id)->firstOrFail();

        $event = DispatchEvent::create([
            'tenant_id' => $this->admin->tenant_id,
            'site_id' => $site->id,
            'client_account_id' => $site->client_account_id,
            'dispatch_number' => 'DSP-PROMOTE-1',
            'event_type' => 'alarm',
            'priority' => DispatchPriority::HIGH,
            'status' => DispatchStatus::OPEN,
            'description' => 'Gate sensor',
            'opened_at' => now(),
            'created_by_user_id' => $this->admin->id,
        ]);

        $incident = app(DispatchService::class)->promoteToIncident($event, $this->admin->id);

        $this->assertNotNull($incident->id);
        $this->assertSame($incident->id, $event->fresh()->incident_id);

        Livewire::actingAs($this->admin)
            ->test(DispatcherBoard::class)
            ->call('selectDispatch', $event->id)
            ->assertSee('Linked incident');
    }

    public function test_analytics_uses_shift_day_for_attendance_and_invoice_day_for_issued_value(): void
    {
        $tenantId = $this->admin->tenant_id;
        $day = today()->subYears(2);
        $template = ShiftAssignment::query()->with('shift')->firstOrFail();
        $assignments = collect(['completed', 'no_show', 'no_show'])->map(function (string $status, int $index) use ($template, $day): ShiftAssignment {
            $start = $day->copy()->setHour(9)->subDays($index === 2 ? 1 : 0);
            $shift = $template->shift->replicate();
            $shift->fill(['starts_at' => $start, 'ends_at' => $start->copy()->addHours(8)])->save();
            $assignment = $template->replicate();
            $assignment->shift()->associate($shift);
            $assignment->status = $status;
            $assignment->save();

            return $assignment;
        });
        $late = $assignments->first();
        AttendanceLog::create([
            'tenant_id' => $tenantId, 'shift_assignment_id' => $late->id,
            'site_id' => $late->shift->site_id, 'guard_id' => $late->guard_id,
            'type' => 'clock_in', 'recorded_at' => $day->copy()->setTime(9, 15),
            'clock_in_at' => $day->copy()->setTime(9, 15), 'status' => 'completed',
        ]);

        $client = ClientAccount::query()->firstOrFail();
        foreach (['sent' => 125.50, 'draft' => 200, 'void' => 400] as $status => $total) {
            Invoice::create([
                'tenant_id' => $tenantId, 'client_account_id' => $client->id,
                'invoice_number' => 'METRIC-'.$status, 'invoice_date' => $day->toDateString(),
                'due_date' => $day->copy()->addDays(30)->toDateString(),
                'status' => $status, 'grand_total' => $total,
            ]);
        }

        $analytics = app(AnalyticsService::class);
        $snapshot = $analytics->snapshot($tenantId, $day->toDateString());
        $this->assertSame(1, $snapshot->late_shifts);
        $this->assertSame(1, $snapshot->no_show_shifts);
        $this->assertSame(125.50, (float) $snapshot->revenue_total);
        $this->assertTrue($snapshot->is($analytics->snapshot($tenantId, $day->toDateString())));
    }

    public function test_analytics_snapshot_for_date(): void
    {
        Livewire::actingAs($this->admin)
            ->test(AnalyticsDashboard::class)
            ->set('snapshotDate', today()->toDateString())
            ->call('refreshSnapshot')
            ->assertHasNoErrors()
            ->assertSee('Snapshot history');
    }

    public function test_draft_invoice_line_items_can_be_edited(): void
    {
        $client = ClientAccount::where('tenant_id', $this->admin->tenant_id)->firstOrFail();
        $invoice = Invoice::create([
            'tenant_id' => $this->admin->tenant_id,
            'client_account_id' => $client->id,
            'invoice_number' => 'INV-EDIT-1',
            'invoice_date' => today()->toDateString(),
            'due_date' => today()->addDays(14)->toDateString(),
            'status' => 'draft',
            'subtotal' => 100,
            'tax_total' => 0,
            'grand_total' => 100,
        ]);

        InvoiceItem::create([
            'tenant_id' => $this->admin->tenant_id,
            'invoice_id' => $invoice->id,
            'description' => 'Old line',
            'quantity' => 1,
            'unit_price' => 100,
            'line_total' => 100,
        ]);

        app(BillingService::class)->syncItems($invoice, [
            ['description' => 'Updated retainer', 'quantity' => 2, 'unit_price' => 250],
        ]);

        $invoice->refresh();
        $this->assertSame(500.0, (float) $invoice->grand_total);
        $this->assertSame('Updated retainer', $invoice->items()->first()->description);

        Livewire::actingAs($this->admin)
            ->test(InvoiceIndex::class)
            ->assertSuccessful()
            ->assertSee('INV-EDIT-1');
    }
}
