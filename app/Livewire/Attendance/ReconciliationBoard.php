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

    public string $search = '';

    protected $queryString = [
        'statusFilter' => ['except' => 'needs_review', 'as' => 'status'],
        'search' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('attendance.manage'), 403);
    }

    public function applyStatFilter(string $key): void
    {
        $this->statusFilter = match ($key) {
            'reconciled' => 'reconciled',
            'all' => 'all',
            default => 'needs_review',
        };
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->statusFilter = 'needs_review';
        $this->search = '';
        $this->resetPage();
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
        $base = AttendanceLog::query()->where('tenant_id', $tenantId);

        $stats = [
            'needs_review' => (clone $base)->whereIn('status', ['late', 'early_leave', 'no_show'])->whereNull('reconciled_at')->count(),
            'late' => (clone $base)->where('status', 'late')->whereNull('reconciled_at')->count(),
            'no_show' => (clone $base)->where('status', 'no_show')->whereNull('reconciled_at')->count(),
            'early_leave' => (clone $base)->where('status', 'early_leave')->whereNull('reconciled_at')->count(),
            'reconciled' => (clone $base)->whereNotNull('reconciled_at')->count(),
        ];

        $query = AttendanceLog::with(['assignedGuard', 'site'])
            ->where('tenant_id', $tenantId)
            ->when($this->statusFilter === 'needs_review', fn ($q) => $q->whereIn('status', ['late', 'early_leave', 'no_show'])->whereNull('reconciled_at'))
            ->when($this->statusFilter === 'reconciled', fn ($q) => $q->whereNotNull('reconciled_at'))
            ->when($this->statusFilter === 'all', fn ($q) => $q)
            ->when(filled($this->search), function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->whereHas('assignedGuard', fn ($g) => $g->where('first_name', 'like', $term)->orWhere('last_name', 'like', $term))
                        ->orWhereHas('site', fn ($s) => $s->where('name', 'like', $term));
                });
            })
            ->latest();

        return view('livewire.attendance.reconciliation-board', [
            'logs' => $query->paginate(25),
            'stats' => $stats,
            'hasActiveFilters' => $this->statusFilter !== 'needs_review' || filled($this->search),
        ])->layout('layouts.app');
    }
}
