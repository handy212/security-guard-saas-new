<?php

namespace App\Livewire\Billing;

use App\Models\AccountingExport;
use App\Models\Guard;
use App\Models\Timesheet;
use App\Services\AccountingExportService;
use App\Services\PayrollService;
use App\Support\TenantContext;
use Livewire\Component;

class PayrollBoard extends Component
{
    public ?int $guardId = null;

    public string $periodStart = '';

    public string $periodEnd = '';

    public function mount(): void
    {
        $this->periodStart = now()->startOfMonth()->toDateString();
        $this->periodEnd = now()->endOfMonth()->toDateString();
    }

    public function generateTimesheet(PayrollService $payroll): void
    {
        abort_unless(auth()->user()->can('payroll.manage'), 403);
        $payroll->generateTimesheet(
            Guard::findOrFail($this->guardId),
            $this->periodStart,
            $this->periodEnd
        );
    }

    public function exportInvoices(AccountingExportService $exports): void
    {
        abort_unless(auth()->user()->can('exports.manage'), 403);
        $exports->exportInvoicesCsv(TenantContext::id());
    }

    public function render()
    {
        abort_unless(auth()->user()->can('payroll.manage'), 403);

        return view('livewire.billing.payroll-board', [
            'timesheets' => Timesheet::with('assignedGuard')->latest()->limit(80)->get(),
            'exports' => AccountingExport::latest()->limit(20)->get(),
            'guards' => Guard::where('status', 'active')->orderBy('first_name')->get(),
        ])->layout('layouts.app');
    }
}
