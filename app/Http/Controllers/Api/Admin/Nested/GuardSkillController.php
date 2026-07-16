<?php

namespace App\Http\Controllers\Api\Admin\Nested;

use App\Models\Guard;
use App\Models\GuardSkill;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuardSkillController extends NestedAdminController
{
    public function index(Request $request, Guard $guard): JsonResponse
    {
        $this->authorize('update', $guard);

        $query = GuardSkill::query()
            ->where('guard_id', $guard->id)
            ->orderBy('skill');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, Guard $guard): JsonResponse
    {
        $this->authorize('update', $guard);

        $skill = GuardSkill::create($this->validatedSkill($request) + [
            'tenant_id' => TenantContext::id(),
            'guard_id' => $guard->id,
        ]);

        return $this->data($skill, 201);
    }

    public function show(Guard $guard, GuardSkill $skill): JsonResponse
    {
        $this->authorize('update', $guard);
        $this->belongsToGuard($guard, $skill);

        return $this->data($skill);
    }

    public function update(Request $request, Guard $guard, GuardSkill $skill): JsonResponse
    {
        $this->authorize('update', $guard);
        $this->belongsToGuard($guard, $skill);

        $skill->update($this->validatedSkill($request, partial: true));

        return $this->data($skill->fresh());
    }

    public function destroy(Guard $guard, GuardSkill $skill): JsonResponse
    {
        $this->authorize('update', $guard);
        $this->belongsToGuard($guard, $skill);
        $skill->delete();

        return $this->noContent();
    }

    private function validatedSkill(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];
        $levels = array_keys(config('guard_hr.skill_levels', []));

        return $request->validate([
            'skill' => [...$required, 'string', 'max:255'],
            'level' => [...$required, 'string', Rule::in($levels)],
        ]);
    }
}
