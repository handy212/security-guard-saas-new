<?php

namespace App\Livewire\Settings;

use App\Models\AuditLog;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $actionFilter = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'actionFilter' => ['except' => 'all', 'as' => 'action'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('view audit trail'), 403);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'actionFilter'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->actionFilter = 'all';
        $this->resetPage();
    }

    public function render()
    {
        abort_unless(auth()->user()->can('view audit trail'), 403);

        $tenantId = TenantContext::id();

        $query = AuditLog::query()
            ->with('user')
            ->where('tenant_id', $tenantId)
            ->when($this->search !== '', function ($q) {
                $needle = '%'.$this->search.'%';
                $q->where(function ($q) use ($needle) {
                    $q->where('action', 'like', $needle)
                        ->orWhereHas('user', fn ($q) => $q->where('name', 'like', $needle)->orWhere('email', 'like', $needle));
                });
            })
            ->when($this->actionFilter !== 'all', fn ($q) => $q->where('action', 'like', $this->actionFilter.'%'))
            ->latest();

        $actions = AuditLog::where('tenant_id', $tenantId)
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action');

        return view('livewire.settings.audit-log-index', [
            'logs' => $query->paginate(25),
            'actions' => $actions,
            'total' => AuditLog::where('tenant_id', $tenantId)->count(),
            'today' => AuditLog::where('tenant_id', $tenantId)->whereDate('created_at', today())->count(),
            'hasActiveFilters' => $this->search !== '' || $this->actionFilter !== 'all',
        ])->layout('layouts.app');
    }
}
