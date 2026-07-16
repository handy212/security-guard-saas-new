<?php

namespace App\Livewire\Visitors;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\HasFormDrawer;
use App\Models\Guard;
use App\Models\Site;
use App\Models\VisitorLog;
use App\Services\VisitorService;
use App\Support\MutableStatus;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class VisitorLogIndex extends Component
{
    use AuthorizesModuleAccess, HasFormDrawer, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public ?int $editingId = null;

    public array $form = [
        'site_id' => '', 'visitor_name' => '', 'visitor_phone' => '', 'company' => '',
        'purpose' => '', 'vehicle_plate' => '', 'id_type' => '', 'id_number' => '', 'guard_id' => '',
    ];

    protected $queryString = ['search' => ['except' => ''], 'statusFilter' => ['except' => 'all', 'as' => 'status']];

    public function mount(): void
    {
        $this->authorizePolicy('viewAny', VisitorLog::class);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function checkIn(VisitorService $service): void
    {
        abort_unless(auth()->user()->can('visitors.manage'), 403);
        $data = $this->validate([
            'form.site_id' => 'required',
            'form.visitor_name' => 'required',
            'form.visitor_phone' => 'nullable',
            'form.company' => 'nullable',
            'form.purpose' => 'nullable',
            'form.vehicle_plate' => 'nullable',
            'form.id_type' => 'nullable|string|max:50',
            'form.id_number' => 'nullable|string|max:120',
            'form.guard_id' => 'nullable',
        ])['form'];

        $data['guard_id'] = $data['guard_id'] ?: null;
        $data['id_type'] = $data['id_type'] ?: null;
        $data['id_number'] = $data['id_number'] ?: null;

        try {
            if ($this->editingId) {
                $visitor = VisitorLog::findOrFail($this->editingId);
                $this->authorize('update', $visitor);
                $service->update($visitor, $data);
                session()->flash('status', 'Visitor updated.');
            } else {
                $service->checkIn($data + ['status' => 'checked_in']);
                session()->flash('status', 'Visitor checked in.');
            }
        } catch (RuntimeException $e) {
            session()->flash('status', $e->getMessage());

            return;
        }

        $this->editingId = null;
        $this->form = $this->blankForm();
        $this->closeDrawer();
    }

    public function openCheckIn(): void
    {
        $this->editingId = null;
        $this->form = $this->blankForm();
        $this->openForm();
    }

    public function edit(int $id): void
    {
        $visitor = VisitorLog::findOrFail($id);
        $this->authorize('update', $visitor);
        abort_unless(MutableStatus::isMutable($visitor), 422);

        $this->editingId = $visitor->id;
        $this->form = [
            'site_id' => (string) $visitor->site_id,
            'visitor_name' => $visitor->visitor_name,
            'visitor_phone' => $visitor->visitor_phone ?? '',
            'company' => $visitor->company ?? '',
            'purpose' => $visitor->purpose ?? '',
            'vehicle_plate' => $visitor->vehicle_plate ?? '',
            'id_type' => $visitor->id_type ?? '',
            'id_number' => $visitor->id_number ?? '',
            'guard_id' => (string) ($visitor->guard_id ?? ''),
        ];
        $this->openForm();
    }

    public function checkOut(VisitorLog $visitor, VisitorService $service): void
    {
        abort_unless(auth()->user()->can('visitors.manage'), 403);
        $service->checkOut($visitor);
        session()->flash('status', 'Visitor checked out.');
    }

    public function delete(int $id, VisitorService $service): void
    {
        $visitor = VisitorLog::findOrFail($id);
        $this->authorize('delete', $visitor);

        try {
            $service->delete($visitor);
        } catch (RuntimeException $e) {
            session()->flash('status', $e->getMessage());

            return;
        }

        session()->flash('status', 'Visitor log deleted.');
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $base = VisitorLog::where('tenant_id', $tenantId);

        return view('livewire.visitors.visitor-log-index', [
            'items' => (clone $base)->with(['site', 'assignedGuard'])
                ->when($this->search, fn ($q) => $q->where('visitor_name', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->paginate(25),
            'sites' => Site::orderBy('name')->get(),
            'guards' => Guard::where('status', 'active')->orderBy('first_name')->get(),
            'stats' => [
                'total' => (clone $base)->count(),
                'on_site' => (clone $base)->where('status', 'checked_in')->count(),
                'today' => (clone $base)->whereDate('checked_in_at', today())->count(),
                'sites' => Site::where('tenant_id', $tenantId)->count(),
            ],
        ])->layout('layouts.app');
    }

    private function blankForm(): array
    {
        return [
            'site_id' => '', 'visitor_name' => '', 'visitor_phone' => '', 'company' => '',
            'purpose' => '', 'vehicle_plate' => '', 'id_type' => '', 'id_number' => '', 'guard_id' => '',
        ];
    }
}
