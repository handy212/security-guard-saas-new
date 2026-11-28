<?php

namespace App\Livewire\Billing;

use App\Models\ClientComplaint;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Services\AssetManagementService;
use App\Services\NavigationBuilder;
use App\Support\TenantContext;
use Livewire\Component;

class BackOfficeHub extends Component
{
    public function mount(): void
    {
        abort_unless(app(NavigationBuilder::class)->linkVisible(['hub' => 'billing']), 403);
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $nav = app(NavigationBuilder::class);
        $links = collect(config('navigation.billing'))->filter(fn (array $link) => $nav->linkVisible($link))->keyBy('href');
        $canBilling = $links->has('/billing/invoices');
        $canExpenses = $links->has('/billing/expenses');
        $canEstimates = $links->has('/billing/estimates');
        $invoices = Invoice::where('tenant_id', $tenantId);
        $estimates = Estimate::where('tenant_id', $tenantId);
        $expenses = Expense::where('tenant_id', $tenantId);
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $openInvoices = $canBilling ? (clone $invoices)->open()->get([
            'id', 'grand_total', 'amount_paid', 'status', 'due_date',
        ]) : collect();

        $amountDue = $openInvoices->sum(fn ($inv) => max(0, (float) $inv->grand_total - (float) ($inv->amount_paid ?? 0)));
        $overdueInvoices = $openInvoices
            ->filter(fn ($invoice) => $invoice->status === 'overdue'
                || ($invoice->due_date?->isPast() && in_array($invoice->status, ['sent', 'partial'], true)));

        $collectedMtd = $canBilling ? InvoicePayment::where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$monthStart.' 00:00:00', $monthEnd.' 23:59:59'])
            ->sum('amount') : 0;

        return view('livewire.billing.back-office-hub', [
            'openComplaints' => $links->has('/clients?tab=complaints')
                ? ClientComplaint::forTenant($tenantId)->where('status', 'open')->count() : null,
            'lowStockCount' => $links->has('/assets')
                ? app(AssetManagementService::class)->inventoryByCategory($tenantId)->where('is_low_stock', true)->count() : null,
            'stats' => [
                'amount_due' => $amountDue,
                'overdue_due' => $overdueInvoices->sum(fn ($inv) => max(0, (float) $inv->grand_total - (float) ($inv->amount_paid ?? 0))),
                'invoices_overdue' => $overdueInvoices->count(),
                'collected_mtd' => (float) $collectedMtd,
                'expenses_mtd' => $canExpenses ? (float) (clone $expenses)->whereBetween('expense_date', [$monthStart, $monthEnd])->sum('amount') : 0,
                'revenue_mtd' => $canBilling ? (float) (clone $invoices)
                    ->whereIn('status', ['sent', 'partial', 'paid', 'overdue'])
                    ->whereBetween('invoice_date', [$monthStart, $monthEnd])
                    ->sum('grand_total') : 0,
                'invoices_open' => $openInvoices->count(),
                'estimates_open' => $canEstimates ? (clone $estimates)->whereIn('status', ['draft', 'sent'])->count() : 0,
                'estimates_pipeline' => $canEstimates ? (float) (clone $estimates)->whereIn('status', ['draft', 'sent'])->sum('grand_total') : 0,
            ],
            'recentInvoices' => $canBilling ? (clone $invoices)->with('clientAccount')->latest()->limit(5)->get() : collect(),
            'recentExpenses' => $canExpenses ? (clone $expenses)->with('category')->latest('expense_date')->limit(5)->get() : collect(),
            'canBilling' => $canBilling,
            'canExpenses' => $canExpenses,
            'canEstimates' => $canEstimates,
        ])->layout('layouts.app');
    }
}
