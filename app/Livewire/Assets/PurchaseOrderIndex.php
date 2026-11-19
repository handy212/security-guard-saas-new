<?php

namespace App\Livewire\Assets;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\HasFormDrawer;
use App\Models\AssetCategory;
use App\Models\AssetPurchaseOrder;
use App\Models\AssetPurchaseOrderItem;
use App\Models\AssetVendor;
use App\Models\EquipmentAsset;
use App\Services\AssetManagementService;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseOrderIndex extends Component
{
    use AuthorizesModuleAccess, HasFormDrawer, WithPagination;

    public ?int $selectedId = null;

    public array $form = [
        'vendor_id' => '', 'expected_date' => '', 'tax_total' => 0, 'notes' => '',
    ];

    public array $items = [['asset_category_id' => '', 'description' => '', 'quantity' => 1, 'unit_cost' => 0]];

    public array $receiveQuantities = [];

    public function mount(): void
    {
        $this->authorizePolicy('viewAny', EquipmentAsset::class);
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

    public function openForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function save(AssetManagementService $service): void
    {
        if ($this->form['vendor_id'] === '') {
            $this->form['vendor_id'] = null;
        }

        $data = $this->validate([
            'form.vendor_id' => ['required', TenantValidation::exists('asset_vendors')],
            'form.expected_date' => 'nullable|date',
            'form.tax_total' => 'nullable|numeric|min:0',
            'form.notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.asset_category_id' => ['nullable', TenantValidation::exists('asset_categories')],
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
        try {
            $service->submitPurchaseOrder(AssetPurchaseOrder::findOrFail($id));
            session()->flash('status', 'Purchase order submitted.');
        } catch (\RuntimeException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function markOrdered(int $id, AssetManagementService $service): void
    {
        try {
            $service->markOrdered(AssetPurchaseOrder::findOrFail($id));
            session()->flash('status', 'Purchase order marked as ordered.');
        } catch (\RuntimeException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function receiveLine(int $poId, int $itemId, AssetManagementService $service): void
    {
        try {
            $po = AssetPurchaseOrder::findOrFail($poId);
            $item = AssetPurchaseOrderItem::where('purchase_order_id', $po->id)->findOrFail($itemId);
            $quantity = (int) ($this->receiveQuantities[$item->id] ?? $item->remainingQuantity());
            $service->receiveItems($po, $item->id, $quantity);
            session()->flash('status', 'Items received into inventory.');
        } catch (\RuntimeException $exception) {
            session()->flash('error', $exception->getMessage());
        }
    }

    public function selectPo(int $id): void
    {
        $po = AssetPurchaseOrder::with('items')->findOrFail($id);
        $this->selectedId = $po->id;
        $this->receiveQuantities = $po->items
            ->mapWithKeys(fn ($item) => [$item->id => $item->remainingQuantity()])
            ->all();
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
