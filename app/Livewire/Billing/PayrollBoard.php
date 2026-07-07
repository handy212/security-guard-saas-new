<?php

namespace App\Livewire\Billing;

use App\Models\Guard;
use App\Models\PayrollExport;
use App\Models\Timesheet;
use App\Services\PayrollExportService;
use App\Services\PayrollService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        session()->flash('status', 'Timesheet generated.');
    }

    public function exportQuickBooks(PayrollExportService $exports): void
    {
        abort_unless(auth()->user()->can('exports.manage'), 403);
        $exports->exportQuickBooks($this->periodStart, $this->periodEnd, auth()->id());
        session()->flash('status', 'Payroll CSV export generated.');
    }

    public function downloadPayrollExport(int $exportId): StreamedResponse
    {
        abort_unless(auth()->user()->can('exports.manage'), 403);
        $export = PayrollExport::where('tenant_id', TenantContext::id())->findOrFail($exportId);
        abort_unless($export->file_path && Storage::exists($export->file_path), 404);

        return Storage::download($export->file_path);
    }

    public function render()
    {
        abort_unless(auth()->user()->can('payroll.manage'), 403);

        $tenantId = TenantContext::id();

        return view('livewire.billing.payroll-board', [
            'timesheets' => Timesheet::with('assignedGuard')->where('tenant_id', $tenantId)->latest()->limit(80)->get(),
            'payrollExports' => PayrollExport::where('tenant_id', $tenantId)->latest()->limit(15)->get(),
            'guards' => Guard::where('status', 'active')->orderBy('first_name')->get(),
            'canExport' => auth()->user()->can('exports.manage'),
        ])->layout('layouts.app');
    }
}
