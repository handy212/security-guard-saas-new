<?php

namespace App\Livewire\Billing;

use App\Models\Guard;
use App\Models\PayrollExport;
use App\Models\Timesheet;
use App\Services\PayrollExportService;
use App\Services\PayrollService;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollBoard extends Component
{
    use WithPagination;

    public ?int $guardId = null;

    public string $periodStart = '';

    public string $periodEnd = '';

    public bool $showGenerateForm = false;

    public array $preparation = [];

    public string $guardSearch = '';

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

    private function periodRules(): array
    {
        return [
            'periodStart' => ['required', 'date_format:Y-m-d'],
            'periodEnd' => ['required', 'date_format:Y-m-d', 'after_or_equal:periodStart'],
        ];
    }

    public function updated(string $property): void
    {
        if ($property === 'guardSearch') {
            $this->resetPage();
        }
        if (in_array($property, ['periodStart', 'periodEnd'], true)) {
            $this->resetPage();
            $this->reset('preparation');
        }
    }

    private function periodTimesheets(): Builder
    {
        return Timesheet::forTenant(TenantContext::id())->where('period_start', $this->periodStart)
            ->where('period_end', $this->periodEnd);
    }

    public function preparePayroll(PayrollService $payroll): void
    {
        abort_unless(auth()->user()->can('payroll.manage'), 403);
        $data = $this->validate($this->periodRules());
        $this->preparation = $payroll->preparePeriod(TenantContext::id(), $data['periodStart'], $data['periodEnd']);
        $this->resetPage();
    }

    public function approvePeriod(): void
    {
        abort_unless(auth()->user()->can('payroll.manage'), 403);
        $this->validate($this->periodRules());
        $approved = $this->periodTimesheets()->whereIn('status', ['draft', 'pending'])->update([
            'status' => 'approved', 'approved_by_user_id' => auth()->id(), 'approved_at' => now(),
        ]);
        session()->flash('status', "Approved {$approved} timesheets for the selected period.");
    }

    public function generateTimesheet(PayrollService $payroll): void
    {
        abort_unless(auth()->user()->can('payroll.manage'), 403);
        $data = $this->validate([
            'guardId' => ['required', TenantValidation::exists('guards')],
            ...$this->periodRules(),
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
        abort_unless(auth()->user()->can('payroll.manage') && auth()->user()->can('exports.manage'), 403);
        $this->validate($this->periodRules());

        if ($this->periodTimesheets()->whereIn('status', ['draft', 'pending'])->exists()) {
            session()->flash('error', 'Approve or reject all pending timesheets for this period before exporting.');

            return;
        }

        try {
            $exports->exportQuickBooks($this->periodStart, $this->periodEnd, auth()->id(), exactPeriod: true);
        } catch (\RuntimeException $exception) {
            session()->flash('error', $exception->getMessage());

            return;
        }
        session()->flash('status', 'Payroll CSV export generated.');
    }

    public function downloadPayrollExport(int $exportId): StreamedResponse
    {
        abort_unless(auth()->user()->can('payroll.manage') && auth()->user()->can('exports.manage'), 403);
        $export = PayrollExport::where('tenant_id', TenantContext::id())->findOrFail($exportId);
        abort_unless($export->file_path && Storage::exists($export->file_path), 404);

        return Storage::download($export->file_path);
    }

    public function approveTimesheet(int $timesheetId): void
    {
        abort_unless(auth()->user()->can('payroll.manage'), 403);
        $timesheet = Timesheet::where('tenant_id', TenantContext::id())->findOrFail($timesheetId);
        $updated = Timesheet::whereKey($timesheet->id)->whereIn('status', ['draft', 'pending'])->update([
            'status' => 'approved',
            'approved_by_user_id' => auth()->id(),
            'approved_at' => now(),
        ]);
        abort_unless($updated, 422);
        session()->flash('status', 'Timesheet approved.');
    }

    public function rejectTimesheet(int $timesheetId): void
    {
        abort_unless(auth()->user()->can('payroll.manage'), 403);
        $timesheet = Timesheet::where('tenant_id', TenantContext::id())->findOrFail($timesheetId);
        $updated = Timesheet::whereKey($timesheet->id)->where('status', $timesheet->status)
            ->whereIn('status', ['draft', 'pending', 'approved'])->update([
                'status' => 'rejected',
                'approved_by_user_id' => auth()->id(),
                'approved_at' => now(),
            ]);
        abort_unless($updated, 422);
        session()->flash('status', 'Timesheet rejected.');
    }

    public function render()
    {
        abort_unless(auth()->user()->can('payroll.manage'), 403);

        $tenantId = TenantContext::id();
        $period = $this->periodTimesheets();

        return view('livewire.billing.payroll-board', [
            'timesheets' => (clone $period)->with('assignedGuard')
                ->when(trim($this->guardSearch) !== '', fn ($query) => $query->whereHas('assignedGuard', function ($guards): void {
                    $search = '%'.trim($this->guardSearch).'%';
                    $guards->where(fn ($query) => $query->where('first_name', 'like', $search)
                        ->orWhere('last_name', 'like', $search)->orWhere('employee_number', 'like', $search));
                }))
                ->orderBy('guard_id')->orderBy('id')->paginate(25),
            'totalTimesheets' => (clone $period)->count(),
            'pendingTimesheets' => (clone $period)->whereIn('status', ['draft', 'pending'])->count(),
            'approvedTimesheets' => (clone $period)->where('status', 'approved')->count(),
            'payrollExports' => PayrollExport::where('tenant_id', $tenantId)->latest()->limit(15)->get(),
            'totalPayrollExports' => PayrollExport::where('tenant_id', $tenantId)->count(),
            'guards' => $this->showGenerateForm ? Guard::forTenant($tenantId)->orderBy('first_name')->get() : collect(),
            'canExport' => auth()->user()->can('exports.manage'),
        ])->layout('layouts.app');
    }
}
