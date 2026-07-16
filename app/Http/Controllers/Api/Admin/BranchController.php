<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Branch;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Branch::class);

        $query = Branch::query()
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term)
                    ->orWhere('city', 'like', $term);
            }))
            ->orderBy('name');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Branch::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
        ]);

        $branch = Branch::create($data + ['tenant_id' => TenantContext::id()]);

        return $this->data($branch, 201);
    }

    public function show(Branch $branch): JsonResponse
    {
        $this->authorize('view', $branch);

        return $this->data($branch);
    }

    public function update(Request $request, Branch $branch): JsonResponse
    {
        $this->authorize('update', $branch);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
        ]);

        $branch->update($data);

        return $this->data($branch->fresh());
    }

    public function destroy(Branch $branch): JsonResponse
    {
        $this->authorize('delete', $branch);
        $branch->delete();

        return $this->noContent();
    }
}
