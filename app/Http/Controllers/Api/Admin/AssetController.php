<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\AssetStatus;
use App\Models\AssetCategory;
use App\Models\EquipmentAsset;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EquipmentAsset::class);

        $query = EquipmentAsset::with(['assetCategory', 'vendor', 'site'])
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where('name', 'like', $term)
                    ->orWhere('asset_tag', 'like', $term)
                    ->orWhere('serial_number', 'like', $term);
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('asset_category_id', $request->integer('category_id')))
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', EquipmentAsset::class);

        $data = $this->validatedAsset($request);
        $category = $data['asset_category_id'] ? AssetCategory::find($data['asset_category_id']) : null;

        $asset = EquipmentAsset::create($data + [
            'tenant_id' => TenantContext::id(),
            'category' => $category?->name,
        ]);

        return $this->data($asset->load(['assetCategory', 'vendor', 'site']), 201);
    }

    public function show(EquipmentAsset $asset): JsonResponse
    {
        $this->authorize('update', $asset);

        return $this->data($asset->load(['assetCategory', 'vendor', 'site']));
    }

    public function update(Request $request, EquipmentAsset $asset): JsonResponse
    {
        $this->authorize('update', $asset);

        $data = $this->validatedAsset($request, partial: true);
        if (array_key_exists('asset_category_id', $data)) {
            $category = $data['asset_category_id'] ? AssetCategory::find($data['asset_category_id']) : null;
            $data['category'] = $category?->name;
        }

        $asset->update($data);

        return $this->data($asset->fresh()->load(['assetCategory', 'vendor', 'site']));
    }

    public function destroy(EquipmentAsset $asset): JsonResponse
    {
        $this->authorize('delete', $asset);
        $asset->delete();

        return $this->noContent();
    }

    private function validatedAsset(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'asset_category_id' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'integer', TenantValidation::exists('asset_categories')],
            'vendor_id' => ['nullable', 'integer', TenantValidation::exists('asset_vendors')],
            'site_id' => ['nullable', 'integer', TenantValidation::exists('sites')],
            'name' => [...$required, 'string', 'max:255'],
            'asset_tag' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'purchase_date' => ['nullable', 'date'],
            'warranty_expires_at' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'quantity_on_hand' => [...$required, 'integer', 'min:1'],
            'condition' => [...$required, 'in:good,fair,poor'],
            'status' => [...$required, Rule::enum(AssetStatus::class)],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
