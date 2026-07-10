<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\HasFormDrawer;
use App\Models\Branch;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class BranchIndex extends Component
{
    use HasFormDrawer, WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public array $form = [
        'name' => '',
        'code' => '',
        'phone' => '',
        'email' => '',
        'address' => '',
        'city' => '',
        'country' => '',
        'is_active' => true,
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $branch = Branch::findOrFail($id);
        $this->editingId = $branch->id;
        $this->form = array_merge($this->form, $branch->only(array_keys($this->form)));
        $this->form['is_active'] = (bool) $branch->is_active;
        $this->showForm = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);

        $data = $this->validate([
            'form.name' => 'required|string|max:255',
            'form.code' => 'nullable|string|max:50',
            'form.phone' => 'nullable|string|max:50',
            'form.email' => 'nullable|email|max:255',
            'form.address' => 'nullable|string|max:500',
            'form.city' => 'nullable|string|max:120',
            'form.country' => 'nullable|string|max:120',
            'form.is_active' => 'boolean',
        ])['form'];

        Branch::updateOrCreate(
            ['id' => $this->editingId],
            $data + ['tenant_id' => TenantContext::id()],
        );

        $this->resetForm();
        $this->showForm = false;
        session()->flash('status', 'Branch saved.');
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        Branch::findOrFail($id)->delete();
        session()->flash('status', 'Branch deleted.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'name' => '',
            'code' => '',
            'phone' => '',
            'email' => '',
            'address' => '',
            'city' => '',
            'country' => '',
            'is_active' => true,
        ];
    }

    public function render()
    {
        return view('livewire.settings.branch-index', [
            'branches' => Branch::query()
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('code', 'like', '%'.$this->search.'%')
                        ->orWhere('city', 'like', '%'.$this->search.'%');
                }))
                ->orderBy('name')
                ->paginate(20),
        ])->layout('layouts.app');
    }
}
