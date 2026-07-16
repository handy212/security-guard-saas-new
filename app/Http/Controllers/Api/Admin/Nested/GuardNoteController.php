<?php

namespace App\Http\Controllers\Api\Admin\Nested;

use App\Models\Guard;
use App\Models\GuardNote;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GuardNoteController extends NestedAdminController
{
    public function index(Request $request, Guard $guard): JsonResponse
    {
        $this->authorize('update', $guard);

        $query = GuardNote::query()
            ->where('guard_id', $guard->id)
            ->with('author')
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, Guard $guard): JsonResponse
    {
        $this->authorize('update', $guard);

        $note = GuardNote::create($this->validatedNote($request) + [
            'tenant_id' => TenantContext::id(),
            'guard_id' => $guard->id,
            'user_id' => $request->user()->id,
        ]);

        return $this->data($note->load('author'), 201);
    }

    public function show(Guard $guard, GuardNote $note): JsonResponse
    {
        $this->authorize('update', $guard);
        $this->belongsToGuard($guard, $note);

        return $this->data($note->load('author'));
    }

    public function update(Request $request, Guard $guard, GuardNote $note): JsonResponse
    {
        $this->authorize('update', $guard);
        $this->belongsToGuard($guard, $note);

        $note->update($this->validatedNote($request, partial: true));

        return $this->data($note->fresh()->load('author'));
    }

    public function destroy(Guard $guard, GuardNote $note): JsonResponse
    {
        $this->authorize('update', $guard);
        $this->belongsToGuard($guard, $note);
        $note->delete();

        return $this->noContent();
    }

    private function validatedNote(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'body' => [...$required, 'string', 'max:5000'],
            'is_internal' => ['boolean'],
        ]);
    }
}
