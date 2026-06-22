<?php

namespace App\Livewire\Equipment;

use App\Models\EquipmentAsset;
use App\Support\TenantContext;
use Livewire\Component;

class EquipmentIndex extends Component
{
    public string $search = '';

    public array $form = [
        'name' => '', 'asset_tag' => '', 'category' => '', 'serial_number' => '', 'condition' => 'good', 'status' => 'available',
    ];

    public ?int $editingId = null;

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
    }

    public function edit(EquipmentAsset $asset): void
    {
        $this->editingId = $asset->id;
        $this->form = $asset->only(array_keys($this->form));
    }

    public function delete(EquipmentAsset $asset): void
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);
        $asset->delete();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->form = ['name' => '', 'asset_tag' => '', 'category' => '', 'serial_number' => '', 'condition' => 'good', 'status' => 'available'];
    }

    public function render()
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);

        return view('livewire.equipment.equipment-index', [
            'items' => EquipmentAsset::query()->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))->latest()->limit(50)->get(),
        ])->layout('layouts.app');
    }
}
