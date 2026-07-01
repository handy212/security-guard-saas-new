<?php

namespace App\Livewire\Visitors;

use App\Livewire\Concerns\HasFormDrawer;
use App\Models\Site;
use App\Models\VisitorLog;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class VisitorLogIndex extends Component
{
    use HasFormDrawer, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public array $form = [
        'site_id' => '', 'visitor_name' => '', 'visitor_phone' => '', 'company' => '', 'purpose' => '', 'vehicle_plate' => '',
    ];

    protected $queryString = ['search' => ['except' => ''], 'statusFilter' => ['except' => 'all', 'as' => 'status']];

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function checkIn(): void
    {
        abort_unless(auth()->user()->can('visitors.manage'), 403);
        $data = $this->validate([
            'form.site_id' => 'required',
            'form.visitor_name' => 'required',
            'form.visitor_phone' => 'nullable',
            'form.company' => 'nullable',
            'form.purpose' => 'nullable',
            'form.vehicle_plate' => 'nullable',
        ])['form'];

        VisitorLog::create($data + [
            'tenant_id' => TenantContext::id(),
            'checked_in_at' => now(),
            'status' => 'checked_in',
        ]);

        $this->form = ['site_id' => '', 'visitor_name' => '', 'visitor_phone' => '', 'company' => '', 'purpose' => '', 'vehicle_plate' => ''];
        $this->closeDrawer();
    }

    public function openCheckIn(): void
    {
        $this->form = ['site_id' => '', 'visitor_name' => '', 'visitor_phone' => '', 'company' => '', 'purpose' => '', 'vehicle_plate' => ''];
        $this->openForm();
    }

    public function checkOut(VisitorLog $visitor): void
    {
        abort_unless(auth()->user()->can('visitors.manage'), 403);
        $visitor->update(['checked_out_at' => now(), 'status' => 'checked_out']);
    }

    public function render()
    {
        abort_unless(auth()->user()->can('visitors.manage'), 403);

        $tenantId = TenantContext::id();
        $base = VisitorLog::where('tenant_id', $tenantId);

        return view('livewire.visitors.visitor-log-index', [
            'items' => (clone $base)->with('site')
                ->when($this->search, fn ($q) => $q->where('visitor_name', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->paginate(25),
            'sites' => Site::orderBy('name')->get(),
            'stats' => [
                'total' => (clone $base)->count(),
                'on_site' => (clone $base)->where('status', 'checked_in')->count(),
                'today' => (clone $base)->whereDate('checked_in_at', today())->count(),
                'sites' => Site::where('tenant_id', $tenantId)->count(),
            ],
        ])->layout('layouts.app');
    }
}
