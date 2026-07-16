<?php

namespace App\Http\Controllers\Api\Admin\Nested;

use App\Models\Guard;
use App\Models\GuardCertification;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuardCertificationController extends NestedAdminController
{
    public function index(Request $request, Guard $guard): JsonResponse
    {
        $this->authorize('update', $guard);

        $query = GuardCertification::query()
            ->where('guard_id', $guard->id)
            ->orderBy('name');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, Guard $guard): JsonResponse
    {
        $this->authorize('update', $guard);

        $certification = GuardCertification::create($this->validatedCertification($request) + [
            'tenant_id' => TenantContext::id(),
            'guard_id' => $guard->id,
            'status' => $request->string('status', 'valid'),
        ]);

        return $this->data($certification, 201);
    }

    public function show(Guard $guard, GuardCertification $certification): JsonResponse
    {
        $this->authorize('update', $guard);
        $this->belongsToGuard($guard, $certification);

        return $this->data($certification);
    }

    public function update(Request $request, Guard $guard, GuardCertification $certification): JsonResponse
    {
        $this->authorize('update', $guard);
        $this->belongsToGuard($guard, $certification);

        $certification->update($this->validatedCertification($request, partial: true));

        return $this->data($certification->fresh());
    }

    public function destroy(Guard $guard, GuardCertification $certification): JsonResponse
    {
        $this->authorize('update', $guard);
        $this->belongsToGuard($guard, $certification);
        $certification->delete();

        return $this->noContent();
    }

    private function validatedCertification(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'name' => [...$required, 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
