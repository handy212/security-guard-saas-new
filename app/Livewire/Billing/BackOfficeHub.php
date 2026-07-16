<?php

namespace App\Livewire\Billing;

use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Support\TenantContext;
use Livewire\Component;

class BackOfficeHub extends Component
{
    public function mount(): void
    {
        $user = auth()->user();
        abort_unless(
            $user?->can('billing.manage')
            || $user?->can('payroll.manage')
            || $user?->can('compliance.manage')
            || $user?->can('analytics.view'),
            403
        );
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $invoices = Invoice::where('tenant_id', $tenantId);
        $estimates = Estimate::where('tenant_id', $tenantId);
        $expenses = Expense::where('tenant_id', $tenantId);
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $openInvoices = (clone $invoices)->whereIn('status', ['draft', 'sent', 'partial', 'overdue'])->get([
            'id', 'grand_total', 'amount_paid', 'status',
        ]);

        $amountDue = $openInvoices->sum(fn ($inv) => max(0, (float) $inv->grand_total - (float) ($inv->amount_paid ?? 0)));
        $overdueDue = $openInvoices
            ->where('status', 'overdue')
            ->sum(fn ($inv) => max(0, (float) $inv->grand_total - (float) ($inv->amount_paid ?? 0)));

        $collectedMtd = InvoicePayment::where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$monthStart.' 00:00:00', $monthEnd.' 23:59:59'])
            ->sum('amount');

        return view('livewire.billing.back-office-hub', [
            'stats' => [
                'amount_due' => $amountDue,
                'overdue_due' => $overdueDue,
                'collected_mtd' => (float) $collectedMtd,
                'expenses_mtd' => (float) (clone $expenses)->whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount'),
                'revenue_mtd' => (float) (clone $invoices)->whereBetween('invoice_date', [$monthStart, $monthEnd])->sum('grand_total'),
                'invoices_open' => $openInvoices->count(),
                'estimates_open' => (clone $estimates)->whereIn('status', ['draft', 'sent'])->count(),
                'estimates_pipeline' => (float) (clone $estimates)->whereIn('status', ['draft', 'sent'])->sum('grand_total'),
            ],
            'recentInvoices' => Invoice::with('clientAccount')->where('tenant_id', $tenantId)->latest()->limit(5)->get(),
            'recentExpenses' => Expense::with('category')->where('tenant_id', $tenantId)->latest('expense_date')->limit(5)->get(),
        ])->layout('layouts.app');
    }
}
