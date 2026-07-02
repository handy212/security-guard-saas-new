<?php

namespace App\Livewire\Assets;

use App\Livewire\Concerns\HasFormDrawer;
use App\Models\AssetCategory;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class CategoryIndex extends Component
{
    use HasFormDrawer, WithPagination;

    public string $search = '';

    public array $form = [
        'name' => '', 'description' => '', 'type' => 'serialized', 'min_stock_level' => 0, 'is_active' => true,
    ];

    public ?int $editingId = null;

    public function save(): void
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);

        $data = $this->validate([
            'form.name' => 'required|string|max:255',
            'form.description' => 'nullable|string',
            'form.type' => 'required|in:serialized,consumable',
            'form.min_stock_level' => 'required|integer|min:0',
            'form.is_active' => 'boolean',
        ])['form'];

        AssetCategory::updateOrCreate(
            ['id' => $this->editingId],
            $data + ['tenant_id' => TenantContext::id()],
        );

        $this->resetForm();
        $this->showForm = false;
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $category = AssetCategory::findOrFail($id);
        $this->editingId = $category->id;
        $this->form = $category->only(array_keys($this->form));
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);
        AssetCategory::findOrFail($id)->delete();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->form = ['name' => '', 'description' => '', 'type' => 'serialized', 'min_stock_level' => 0, 'is_active' => true];
    }

    public function render()
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);

        return view('livewire.assets.category-index', [
            'categories' => AssetCategory::where('tenant_id', TenantContext::id())
                ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->withCount('assets')
                ->orderBy('name')
                ->paginate(20),
            'types' => config('assets.category_types'),
        ])->layout('layouts.app');
    }
}
