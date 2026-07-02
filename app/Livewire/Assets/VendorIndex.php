<?php

namespace App\Livewire\Assets;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\HasFormDrawer;
use App\Models\AssetVendor;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class VendorIndex extends Component
{
    use AuthorizesModuleAccess, HasFormDrawer, WithPagination;

    public string $search = '';

    public array $form = [
        'name' => '', 'contact_name' => '', 'email' => '', 'phone' => '', 'address' => '', 'status' => 'active',
    ];

    public ?int $editingId = null;

    public function mount(): void
    {
        $this->authorizePermission('equipment.manage');
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);

        $data = $this->validate([
            'form.name' => 'required|string|max:255',
            'form.contact_name' => 'nullable|string|max:255',
            'form.email' => 'nullable|email|max:255',
            'form.phone' => 'nullable|string|max:50',
            'form.address' => 'nullable|string',
            'form.status' => 'required|in:active,inactive',
        ])['form'];

        AssetVendor::updateOrCreate(
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
        $vendor = AssetVendor::findOrFail($id);
        $this->editingId = $vendor->id;
        $this->form = $vendor->only(array_keys($this->form));
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);
        AssetVendor::findOrFail($id)->delete();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->form = ['name' => '', 'contact_name' => '', 'email' => '', 'phone' => '', 'address' => '', 'status' => 'active'];
    }

    public function render()
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);

        return view('livewire.assets.vendor-index', [
            'vendors' => AssetVendor::where('tenant_id', TenantContext::id())
                ->when($this->search, fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->withCount(['purchaseOrders', 'assets'])
                ->orderBy('name')
                ->paginate(20),
        ])->layout('layouts.app');
    }
}
