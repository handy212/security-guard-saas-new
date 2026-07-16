<?php

namespace App\Livewire\Assets;

use App\Enums\AssetStatus;
use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\HasFormDrawer;
use App\Models\AssetCategory;
use App\Models\AssetVendor;
use App\Models\EquipmentAsset;
use App\Models\Guard;
use App\Models\Site;
use App\Services\AssetManagementService;
use App\Support\TenantContext;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class AssetIndex extends Component
{
    use AuthorizesModuleAccess, HasFormDrawer, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public ?int $categoryFilter = null;

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public array $form = [
        'asset_category_id' => '', 'vendor_id' => '', 'site_id' => '',
        'name' => '', 'asset_tag' => '', 'serial_number' => '', 'model' => '', 'manufacturer' => '',
        'description' => '', 'purchase_cost' => '', 'purchase_date' => '', 'warranty_expires_at' => '',
        'location' => '', 'quantity_on_hand' => 1, 'condition' => 'good', 'status' => 'available', 'notes' => '',
    ];

    public ?int $editingId = null;

    public bool $showIssueForm = false;

    public ?int $issueAssetId = null;

    public array $issueForm = ['guard_id' => '', 'site_id' => '', 'issue_notes' => ''];

    public bool $showReturnForm = false;

    public ?int $returnAssignmentId = null;

    public string $returnNotes = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
        'categoryFilter' => ['except' => null, 'as' => 'category'],
    ];

    public function mount(AssetManagementService $assets): void
    {
        $this->authorizePolicy('viewAny', EquipmentAsset::class);
        $assets->ensureDeployKitCatalog(TenantContext::id());
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'categoryFilter'], true)) {
            $this->resetPage();
        }
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);
        $this->normalizeForm();

        $data = $this->validate([
            'form.asset_category_id' => 'nullable|exists:asset_categories,id',
            'form.vendor_id' => 'nullable|exists:asset_vendors,id',
            'form.site_id' => 'nullable|exists:sites,id',
            'form.name' => 'required|string|max:255',
            'form.asset_tag' => 'nullable|string|max:100',
            'form.serial_number' => 'nullable|string|max:100',
            'form.model' => 'nullable|string|max:100',
            'form.manufacturer' => 'nullable|string|max:100',
            'form.description' => 'nullable|string',
            'form.purchase_cost' => 'nullable|numeric|min:0',
            'form.purchase_date' => 'nullable|date',
            'form.warranty_expires_at' => 'nullable|date',
            'form.location' => 'nullable|string|max:255',
            'form.quantity_on_hand' => 'required|integer|min:1',
            'form.condition' => 'required|in:good,fair,poor',
            'form.status' => ['required', Rule::enum(AssetStatus::class)],
            'form.notes' => 'nullable|string',
        ])['form'];

        $category = $data['asset_category_id'] ? AssetCategory::find($data['asset_category_id']) : null;

        EquipmentAsset::updateOrCreate(
            ['id' => $this->editingId],
            $data + [
                'tenant_id' => TenantContext::id(),
                'category' => $category?->name,
            ],
        );

        $this->resetForm();
        $this->showForm = false;
        session()->flash('status', $this->editingId ? 'Asset updated.' : 'Asset created.');
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $asset = EquipmentAsset::findOrFail($id);
        $this->editingId = $asset->id;
        $this->form = [
            'asset_category_id' => $asset->asset_category_id ?? '',
            'vendor_id' => $asset->vendor_id ?? '',
            'site_id' => $asset->site_id ?? '',
            'name' => $asset->name,
            'asset_tag' => $asset->asset_tag ?? '',
            'serial_number' => $asset->serial_number ?? '',
            'model' => $asset->model ?? '',
            'manufacturer' => $asset->manufacturer ?? '',
            'description' => $asset->description ?? '',
            'purchase_cost' => $asset->purchase_cost ?? '',
            'purchase_date' => $asset->purchase_date?->toDateString() ?? '',
            'warranty_expires_at' => $asset->warranty_expires_at?->toDateString() ?? '',
            'location' => $asset->location ?? '',
            'quantity_on_hand' => $asset->quantity_on_hand ?? 1,
            'condition' => $asset->condition,
            'status' => $asset->status->value,
            'notes' => $asset->notes ?? '',
        ];
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);
        EquipmentAsset::findOrFail($id)->delete();
    }

    public function openIssue(int $id): void
    {
        $this->issueAssetId = $id;
        $this->issueForm = ['guard_id' => '', 'site_id' => '', 'issue_notes' => ''];
        $this->showIssueForm = true;
    }

    public function issue(AssetManagementService $service): void
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);
        foreach (['guard_id', 'site_id'] as $key) {
            if (($this->issueForm[$key] ?? '') === '') {
                $this->issueForm[$key] = null;
            }
        }

        $data = $this->validate([
            'issueAssetId' => 'required|exists:equipment_assets,id',
            'issueForm.guard_id' => 'nullable|exists:guards,id',
            'issueForm.site_id' => 'nullable|exists:sites,id',
            'issueForm.issue_notes' => 'nullable|string',
        ]);

        $asset = EquipmentAsset::findOrFail($data['issueAssetId']);

        try {
            $service->issue($asset, $data['issueForm'] + ['tenant_id' => TenantContext::id()]);
        } catch (\RuntimeException $e) {
            $this->addError('issueAssetId', $e->getMessage());

            return;
        }

        $this->showIssueForm = false;
        session()->flash('status', 'Asset issued.');
    }

    public function closeIssueForm(): void
    {
        $this->showIssueForm = false;
    }

    public function openReturn(int $assignmentId): void
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);
        $this->returnAssignmentId = $assignmentId;
        $this->returnNotes = '';
        $this->showReturnForm = true;
    }

    public function closeReturnForm(): void
    {
        $this->showReturnForm = false;
        $this->returnAssignmentId = null;
        $this->returnNotes = '';
    }

    public function returnAssignment(AssetManagementService $service): void
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);
        $data = $this->validate([
            'returnAssignmentId' => 'required|exists:equipment_assignments,id',
            'returnNotes' => 'nullable|string|max:1000',
        ]);

        $assignment = \App\Models\EquipmentAssignment::findOrFail($data['returnAssignmentId']);
        $service->returnAsset($assignment, $data['returnNotes'] ?: null);
        $this->closeReturnForm();
        session()->flash('status', 'Asset returned.');
    }

    public function render()
    {
        abort_unless(auth()->user()->can('equipment.manage'), 403);
        $tenantId = TenantContext::id();
        $base = EquipmentAsset::where('tenant_id', $tenantId);

        return view('livewire.assets.asset-index', [
            'items' => (clone $base)
                ->with([
                    'assetCategory', 'vendor', 'site',
                    'assignments' => fn ($q) => $q->where('status', 'issued')->with('assignedGuard'),
                ])
                ->when($this->search, fn ($q) => $q->where(function ($inner) {
                    $inner->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('asset_tag', 'like', '%'.$this->search.'%')
                        ->orWhere('serial_number', 'like', '%'.$this->search.'%');
                }))
                ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->categoryFilter, fn ($q) => $q->where('asset_category_id', (int) $this->categoryFilter))
                ->latest()
                ->paginate(25),
            'categories' => AssetCategory::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(),
            'vendors' => AssetVendor::where('tenant_id', $tenantId)->orderBy('name')->get(),
            'sites' => Site::where('tenant_id', $tenantId)->orderBy('name')->get(),
            'guards' => Guard::where('tenant_id', $tenantId)->where('status', 'active')->orderBy('first_name')->get(),
            'statuses' => config('assets.statuses'),
        ])->layout('layouts.app');
    }

    private function normalizeForm(): void
    {
        foreach (['asset_category_id', 'vendor_id', 'site_id', 'purchase_cost', 'purchase_date', 'warranty_expires_at'] as $key) {
            if (($this->form[$key] ?? '') === '') {
                $this->form[$key] = null;
            }
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'asset_category_id' => '', 'vendor_id' => '', 'site_id' => '',
            'name' => '', 'asset_tag' => '', 'serial_number' => '', 'model' => '', 'manufacturer' => '',
            'description' => '', 'purchase_cost' => '', 'purchase_date' => '', 'warranty_expires_at' => '',
            'location' => '', 'quantity_on_hand' => 1, 'condition' => 'good', 'status' => 'available', 'notes' => '',
        ];
    }
}
