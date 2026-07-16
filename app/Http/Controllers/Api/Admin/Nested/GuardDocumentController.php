<?php

namespace App\Http\Controllers\Api\Admin\Nested;

use App\Enums\GuardDocumentType;
use App\Models\Guard;
use App\Models\GuardDocument;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuardDocumentController extends NestedAdminController
{
    public function index(Request $request, Guard $guard): JsonResponse
    {
        $this->authorize('update', $guard);

        $query = GuardDocument::query()
            ->where('guard_id', $guard->id)
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, Guard $guard): JsonResponse
    {
        $this->authorize('update', $guard);

        $data = $this->validatedDocument($request);

        $document = GuardDocument::create([
            ...$data,
            'tenant_id' => TenantContext::id(),
            'guard_id' => $guard->id,
            'status' => 'valid',
        ]);

        return $this->data($document, 201);
    }

    public function show(Guard $guard, GuardDocument $document): JsonResponse
    {
        $this->authorize('update', $guard);
        $this->belongsToGuard($guard, $document);

        return $this->data($document);
    }

    public function update(Request $request, Guard $guard, GuardDocument $document): JsonResponse
    {
        $this->authorize('update', $guard);
        $this->belongsToGuard($guard, $document);

        $document->update($this->validatedDocument($request, partial: true));

        return $this->data($document->fresh());
    }

    public function destroy(Guard $guard, GuardDocument $document): JsonResponse
    {
        $this->authorize('update', $guard);
        $this->belongsToGuard($guard, $document);
        $document->delete();

        return $this->noContent();
    }

    private function validatedDocument(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'type' => [...$required, Rule::enum(GuardDocumentType::class)],
            'expires_at' => ['nullable', 'date'],
            'file_path' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
