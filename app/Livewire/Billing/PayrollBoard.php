<?php

namespace App\Livewire\Billing;

use App\Models\Guard;
use App\Models\PayrollExport;
use App\Models\Timesheet;
use App\Services\PayrollExportService;
use App\Services\PayrollService;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollBoard extends Component
{
    public ?int $guardId = null;

    public string $periodStart = '';

    public string $periodEnd = '';

    public bool $showGenerateForm = false;

    public function mount(): void
    {
        $this->periodStart = now()->startOfMonth()->toDateString();
        $this->periodEnd = now()->endOfMonth()->toDateString();
    }

    public function openGenerate(): void
    {
        $this->showGenerateForm = true;
    }

    public function closeGenerate(): void
    {
        $this->showGenerateForm = false;
    }

    public function generateTimesheet(PayrollService $payroll): void
    {
        abort_unless(auth()->user()->can('payroll.manage'), 403);
        $data = $this->validate([
            'guardId' => ['required', TenantValidation::exists('guards')],
            'periodStart' => ['required', 'date'],
            'periodEnd' => ['required', 'date', 'after_or_equal:periodStart'],
        ]);

        try {
            $payroll->generateTimesheet(
                Guard::findOrFail($data['guardId']),
                $data['periodStart'],
                $data['periodEnd']
            );
        } catch (\RuntimeException $exception) {
            $this->addError('periodEnd', $exception->getMessage());

            return;
        }
        $this->showGenerateForm = false;
        session()->flash('status', 'Timesheet generated.');
    }

    public function exportQuickBooks(PayrollExportService $exports): void
    {
        abort_unless(auth()->user()->can('exports.manage'), 403);
        $this->validate([
            'periodStart' => ['required', 'date'],
            'periodEnd' => ['required', 'date', 'after_or_equal:periodStart'],
        ]);

        try {
            $exports->exportQuickBooks($this->periodStart, $this->periodEnd, auth()->id());
        } catch (\RuntimeException $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }
        session()->flash('status', 'Payroll CSV export generated.');
    }

    public function downloadPayrollExport(int $exportId): StreamedResponse
    {
        abort_unless(auth()->user()->can('exports.manage'), 403);
        $export = PayrollExport::where('tenant_id', TenantContext::id())->findOrFail($exportId);
        abort_unless($export->file_path && Storage::exists($export->file_path), 404);

        return Storage::download($export->file_path);
    }

    public function approveTimesheet(int $timesheetId): void
    {
        abort_unless(auth()->user()->can('payroll.manage'), 403);
        $timesheet = Timesheet::where('tenant_id', TenantContext::id())->findOrFail($timesheetId);
        abort_unless($timesheet->status === 'pending', 422);

        $timesheet->update([
            'status' => 'approved',
            'approved_by_user_id' => auth()->id(),
            'approved_at' => now(),
        ]);

        session()->flash('status', 'Timesheet approved.');
    }

    public function rejectTimesheet(int $timesheetId): void
    {
        abort_unless(auth()->user()->can('payroll.manage'), 403);
        $timesheet = Timesheet::where('tenant_id', TenantContext::id())->findOrFail($timesheetId);
        abort_unless(in_array($timesheet->status, ['pending', 'approved'], true), 422);

        $timesheet->update([
            'status' => 'rejected',
            'approved_by_user_id' => auth()->id(),
            'approved_at' => now(),
        ]);

        session()->flash('status', 'Timesheet rejected.');
    }

    public function render()
    {
        abort_unless(auth()->user()->can('payroll.manage'), 403);

        $tenantId = TenantContext::id();

        return view('livewire.billing.payroll-board', [
            'timesheets' => Timesheet::with('assignedGuard')->where('tenant_id', $tenantId)->latest()->limit(80)->get(),
            'totalTimesheets' => Timesheet::where('tenant_id', $tenantId)->count(),
            'pendingTimesheets' => Timesheet::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'payrollExports' => PayrollExport::where('tenant_id', $tenantId)->latest()->limit(15)->get(),
            'totalPayrollExports' => PayrollExport::where('tenant_id', $tenantId)->count(),
            'guards' => Guard::where('status', 'active')->orderBy('first_name')->get(),
            'canExport' => auth()->user()->can('exports.manage'),
        ])->layout('layouts.app');
    }
}
