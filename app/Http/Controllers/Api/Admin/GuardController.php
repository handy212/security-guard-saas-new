<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Api\Admin\StoreGuardRequest;
use App\Http\Requests\Api\Admin\UpdateGuardRequest;
use App\Models\Guard;
use App\Models\Tenant;
use App\Services\PlanLimitService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuardController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Guard::class);

        $query = Guard::with('branch')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where('first_name', 'like', $term)
                    ->orWhere('last_name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('employee_number', 'like', $term);
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->orderBy('first_name');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(StoreGuardRequest $request, PlanLimitService $limits): JsonResponse
    {
        $tenant = Tenant::findOrFail(TenantContext::id());
        abort_unless($limits->canCreateGuard($tenant), 403, 'Guard limit reached for your plan.');

        $guard = Guard::create($request->validated() + ['tenant_id' => TenantContext::id()]);

        return $this->data($guard->load('branch'), 201);
    }

    public function show(Guard $guard): JsonResponse
    {
        $this->authorize('update', $guard);

        return $this->data($guard->load('branch'));
    }

    public function update(UpdateGuardRequest $request, Guard $guard): JsonResponse
    {
        $guard->update($request->validated());

        return $this->data($guard->fresh()->load('branch'));
    }

    public function destroy(Guard $guard): JsonResponse
    {
        $this->authorize('delete', $guard);
        $guard->delete();

        return $this->noContent();
    }
}
