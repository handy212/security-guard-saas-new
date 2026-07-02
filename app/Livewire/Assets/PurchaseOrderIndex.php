<?php

namespace App\Livewire\Assets;

use App\Models\AssetCategory;
use App\Models\AssetPurchaseOrder;
use App\Models\AssetVendor;
use App\Services\AssetManagementService;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseOrderIndex extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $selectedId = null;

    public array $form = [
        'vendor_id' => '', 'expected_date' => '', 'tax_total' => 0, 'notes' => '',
    ];

    public array $items = [['asset_category_id' => '', 'description' => '', 'quantity' => 1, 'unit_cost' => 0]];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);
    }

    public function addLine(): void
    {
        $this->items[] = ['asset_category_id' => '', 'description' => '', 'quantity' => 1, 'unit_cost' => 0];
    }

    public function removeLine(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(AssetManagementService $service): void
    {
        if ($this->form['vendor_id'] === '') {
            $this->form['vendor_id'] = null;
        }

        $data = $this->validate([
            'form.vendor_id' => 'required|exists:asset_vendors,id',
            'form.expected_date' => 'nullable|date',
            'form.tax_total' => 'nullable|numeric|min:0',
            'form.notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.asset_category_id' => 'nullable|exists:asset_categories,id',
        ]);

        $po = $service->createPurchaseOrder(
            $data['form'] + ['tenant_id' => TenantContext::id()],
            $data['items'],
            TenantContext::userId(),
        );

        $this->selectedId = $po->id;
        $this->showForm = false;
        $this->resetForm();
        session()->flash('status', "Purchase order {$po->po_number} created.");
    }

    public function submitPo(int $id, AssetManagementService $service): void
    {
        $service->submitPurchaseOrder(AssetPurchaseOrder::findOrFail($id));
        session()->flash('status', 'Purchase order submitted.');
    }

    public function markOrdered(int $id, AssetManagementService $service): void
    {
        $service->markOrdered(AssetPurchaseOrder::findOrFail($id));
        session()->flash('status', 'Purchase order marked as ordered.');
    }

    public function receiveLine(int $poId, int $itemId, AssetManagementService $service): void
    {
        $item = \App\Models\AssetPurchaseOrderItem::findOrFail($itemId);
        $service->receiveItems(AssetPurchaseOrder::findOrFail($poId), $itemId, $item->remainingQuantity());
        session()->flash('status', 'Items received into inventory.');
    }

    public function selectPo(int $id): void
    {
        $this->selectedId = $id;
    }

    private function resetForm(): void
    {
        $this->form = ['vendor_id' => '', 'expected_date' => '', 'tax_total' => 0, 'notes' => ''];
        $this->items = [['asset_category_id' => '', 'description' => '', 'quantity' => 1, 'unit_cost' => 0]];
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        return view('livewire.assets.purchase-order-index', [
            'orders' => AssetPurchaseOrder::with('vendor')
                ->where('tenant_id', $tenantId)
                ->latest()
                ->paginate(15),
            'selected' => $this->selectedId
                ? AssetPurchaseOrder::with(['vendor', 'items.category'])->find($this->selectedId)
                : null,
            'vendors' => AssetVendor::where('tenant_id', $tenantId)->where('status', 'active')->orderBy('name')->get(),
            'categories' => AssetCategory::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(),
            'poStatuses' => config('assets.po_statuses'),
        ])->layout('layouts.app');
    }
}
