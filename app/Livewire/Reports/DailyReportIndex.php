<?php

namespace App\Livewire\Reports;

use App\Models\DailyActivityReport;
use App\Models\Site;
use App\Services\ReportService;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class DailyReportIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $siteFilter = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
        'siteFilter' => ['except' => '', 'as' => 'site'],
        'dateFrom' => ['except' => '', 'as' => 'from'],
        'dateTo' => ['except' => '', 'as' => 'to'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('reports.approve'), 403);
        $this->validateFilters();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'siteFilter', 'dateFrom', 'dateTo'], true)) {
            $this->resetPage();
        }

        if (in_array($property, ['siteFilter', 'dateFrom', 'dateTo'], true)) {
            $rules = $this->filterRules();
            $this->validateOnly($property, $rules);

            if ($property === 'dateFrom' && $this->dateTo !== '') {
                $this->validateOnly('dateTo', $rules);
            }
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->siteFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetErrorBag();
        $this->resetPage();
    }

    public function approve(DailyActivityReport $report, ReportService $service): void
    {
        $this->authorize('approve', $report);
        $service->approve($report, auth()->id());
        session()->flash('status', 'Report approved.');
    }


    private function filterRules(): array
    {
        $rules = [
            'siteFilter' => ['nullable'],
            'dateFrom' => ['nullable'],
            'dateTo' => ['nullable'],
        ];

        if ($this->siteFilter !== '') {
            $rules['siteFilter'] = [
                'integer',
                Rule::exists('sites', 'id')->where('tenant_id', TenantContext::id()),
            ];
        }

        if ($this->dateFrom !== '') {
            $rules['dateFrom'] = ['date_format:Y-m-d'];
        }

        if ($this->dateTo !== '') {
            $rules['dateTo'] = ['date_format:Y-m-d'];
            if ($this->dateFrom !== '') {
                $rules['dateTo'][] = 'after_or_equal:dateFrom';
            }
        }

        return $rules;
    }

    private function validateFilters(): void
    {
        $this->validate($this->filterRules());
    }

    public function render()
    {
        abort_unless(auth()->user()->can('reports.approve'), 403);

        $tenantId = TenantContext::id();
        $base = DailyActivityReport::where('tenant_id', $tenantId);
        $sites = Site::query()->where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $validationErrors = $this->getErrorBag();
        $hasInvalidFilters = $validationErrors->has('siteFilter')
            || $validationErrors->has('dateFrom')
            || $validationErrors->has('dateTo');

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
            ->when($hasInvalidFilters, fn ($q) => $q->whereRaw('1 = 0'))
            ->when($this->siteFilter !== '' && ! $validationErrors->has('siteFilter'), fn ($q) => $q->where('site_id', (int) $this->siteFilter))
            ->when($this->dateFrom !== '' && ! $validationErrors->has('dateFrom'), fn ($q) => $q->whereDate('report_date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '' && ! $validationErrors->has('dateTo'), fn ($q) => $q->whereDate('report_date', '<=', $this->dateTo))
            ->latest();

        $periodLabel = null;
        if (! $validationErrors->has('dateFrom') && ! $validationErrors->has('dateTo')) {
            if ($this->dateFrom !== '' && $this->dateTo !== '') {
                $periodLabel = Carbon::parse($this->dateFrom)->format('M j, Y').' – '.Carbon::parse($this->dateTo)->format('M j, Y');
            } elseif ($this->dateFrom !== '') {
                $periodLabel = 'From '.Carbon::parse($this->dateFrom)->format('M j, Y');
            } elseif ($this->dateTo !== '') {
                $periodLabel = 'Through '.Carbon::parse($this->dateTo)->format('M j, Y');
            }
        }

        $activeFilterLabels = [];
        if ($this->search !== '') {
            $activeFilterLabels[] = 'Search: '.$this->search;
        }
        if ($this->statusFilter !== 'all') {
            $activeFilterLabels[] = 'Status: '.ucfirst(str_replace('_', ' ', $this->statusFilter));
        }
        if ($this->siteFilter !== '' && ! $validationErrors->has('siteFilter')) {
            $activeFilterLabels[] = 'Site: '.$sites->firstWhere('id', (int) $this->siteFilter)?->name;
        }
        if ($periodLabel !== null) {
            $activeFilterLabels[] = 'Report period: '.$periodLabel;
        }

        return view('livewire.reports.daily-report-index', [
            'reports' => $query->paginate(25),
            'sites' => $sites,
            'activeFilterLabels' => $activeFilterLabels,
            'stats' => [
                'total' => (clone $base)->count(),
                'pending' => (clone $base)->where('status', 'submitted')->count(),
                'approved' => (clone $base)->where('status', 'approved')->count(),
                'today' => (clone $base)->whereDate('report_date', today())->count(),
            ],
            'hasActiveFilters' => $this->search !== '' || $this->statusFilter !== 'all'
                || $this->siteFilter !== '' || $this->dateFrom !== '' || $this->dateTo !== '',
        ])->layout('layouts.app');
    }
}
