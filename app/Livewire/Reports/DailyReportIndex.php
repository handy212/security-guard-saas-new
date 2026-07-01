<?php

namespace App\Livewire\Reports;

use App\Models\DailyActivityReport;
use App\Services\ReportService;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class DailyReportIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('reports.approve'), 403);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function approve(DailyActivityReport $report, ReportService $service): void
    {
        $this->authorize('approve', $report);
        $service->approve($report, auth()->id());
        session()->flash('status', 'Report approved.');
    }

    public function render()
    {
        abort_unless(auth()->user()->can('reports.approve'), 403);

        $tenantId = TenantContext::id();
        $base = DailyActivityReport::where('tenant_id', $tenantId);

        $query = (clone $base)->with(['site', 'assignedGuard'])
            ->when($this->search !== '', function ($q) {
                $needle = '%'.$this->search.'%';
                $q->where(function ($q) use ($needle) {
                    $q->where('title', 'like', $needle)
                        ->orWhereHas('site', fn ($q) => $q->where('name', 'like', $needle))
                        ->orWhereHas('assignedGuard', fn ($q) => $q->where('first_name', 'like', $needle)->orWhere('last_name', 'like', $needle));
                });
            })
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest();

        return view('livewire.reports.daily-report-index', [
            'reports' => $query->paginate(25),
            'stats' => [
                'total' => (clone $base)->count(),
                'pending' => (clone $base)->where('status', 'submitted')->count(),
                'approved' => (clone $base)->where('status', 'approved')->count(),
                'today' => (clone $base)->whereDate('report_date', today())->count(),
            ],
            'hasActiveFilters' => $this->search !== '' || $this->statusFilter !== 'all',
        ])->layout('layouts.app');
    }
}
