<?php

namespace App\Livewire\Attendance;

use App\Models\AttendanceLog;
use App\Services\PayrollExportService;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class ReconciliationBoard extends Component
{
    use WithPagination;

    public string $statusFilter = 'needs_review';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('attendance.manage'), 403);
    }

    public function reconcile(int $logId, PayrollExportService $service, ?string $newStatus = null): void
    {
        $log = AttendanceLog::findOrFail($logId);
        $service->reconcileAttendance($log, auth()->id(), 'Reconciled by manager', $newStatus ?? 'reconciled');
        session()->flash('status', 'Attendance reconciled.');
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $query = AttendanceLog::with(['assignedGuard', 'site'])
            ->where('tenant_id', $tenantId)
            ->when($this->statusFilter === 'needs_review', fn ($q) => $q->whereIn('status', ['late', 'early_leave', 'no_show'])->whereNull('reconciled_at'))
            ->when($this->statusFilter === 'reconciled', fn ($q) => $q->whereNotNull('reconciled_at'))
            ->when($this->statusFilter === 'all', fn ($q) => $q)
            ->latest();

        return view('livewire.attendance.reconciliation-board', [
            'logs' => $query->paginate(25),
        ])->layout('layouts.app');
    }
}
