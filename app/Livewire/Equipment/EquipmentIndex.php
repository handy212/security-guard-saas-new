<?php

namespace App\Livewire\Equipment;

use App\Livewire\Concerns\HasFormDrawer;
use App\Models\EquipmentAsset;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class EquipmentIndex extends Component
{
    use HasFormDrawer, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public array $form = [
        'name' => '', 'asset_tag' => '', 'category' => '', 'serial_number' => '', 'condition' => 'good', 'status' => 'available',
    ];

    public ?int $editingId = null;

    protected $queryString = ['search' => ['except' => ''], 'statusFilter' => ['except' => 'all', 'as' => 'status']];

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);
        $data = $this->validate([
            'form.name' => 'required',
            'form.asset_tag' => 'nullable',
            'form.category' => 'nullable',
            'form.serial_number' => 'nullable',
            'form.condition' => 'required',
            'form.status' => 'required',
        ])['form'];

        EquipmentAsset::updateOrCreate(
            ['id' => $this->editingId],
            $data + ['tenant_id' => TenantContext::id()]
        );

        $this->resetForm();
        $this->closeDrawer();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->openForm();
    }

    public function edit(int $id): void
    {
        $asset = EquipmentAsset::findOrFail($id);
        $this->editingId = $asset->id;
        $this->form = $asset->only(array_keys($this->form));
        $this->openForm();
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);
        EquipmentAsset::findOrFail($id)->delete();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->form = ['name' => '', 'asset_tag' => '', 'category' => '', 'serial_number' => '', 'condition' => 'good', 'status' => 'available'];
    }

    public function render()
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);

        $tenantId = TenantContext::id();
        $base = EquipmentAsset::where('tenant_id', $tenantId);

        return view('livewire.equipment.equipment-index', [
            'items' => (clone $base)->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->paginate(25),
            'stats' => [
                'total' => (clone $base)->count(),
                'available' => (clone $base)->where('status', 'available')->count(),
                'issued' => (clone $base)->where('status', 'issued')->count(),
                'retired' => (clone $base)->where('status', 'retired')->count(),
            ],
        ])->layout('layouts.app');
    }
}
