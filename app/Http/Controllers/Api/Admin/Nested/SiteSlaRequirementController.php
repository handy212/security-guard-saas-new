<?php

namespace App\Http\Controllers\Api\Admin\Nested;

use App\Models\Site;
use App\Models\SiteSlaRequirement;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteSlaRequirementController extends NestedAdminController
{
    public function index(Request $request, Site $site): JsonResponse
    {
        $this->authorize('view', $site);

        $query = SiteSlaRequirement::query()
            ->where('site_id', $site->id)
            ->orderBy('metric');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, Site $site): JsonResponse
    {
        $this->authorize('update', $site);

        $requirement = SiteSlaRequirement::create($this->validatedRequirement($request) + [
            'tenant_id' => TenantContext::id(),
            'site_id' => $site->id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return $this->data($requirement, 201);
    }

    public function show(Site $site, SiteSlaRequirement $slaRequirement): JsonResponse
    {
        $this->authorize('view', $site);
        $this->belongsToSite($site, $slaRequirement);

        return $this->data($slaRequirement);
    }

    public function update(Request $request, Site $site, SiteSlaRequirement $slaRequirement): JsonResponse
    {
        $this->authorize('update', $site);
        $this->belongsToSite($site, $slaRequirement);

        $slaRequirement->update($this->validatedRequirement($request, partial: true));

        return $this->data($slaRequirement->fresh());
    }

    public function destroy(Site $site, SiteSlaRequirement $slaRequirement): JsonResponse
    {
        $this->authorize('update', $site);
        $this->belongsToSite($site, $slaRequirement);
        $slaRequirement->delete();

        return $this->noContent();
    }

    private function validatedRequirement(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'metric' => [...$required, 'string', 'max:255'],
            'target_value' => [...$required, 'string', 'max:120'],
            'frequency' => [...$required, 'string', 'in:daily,weekly,monthly'],
            'grace_minutes' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);
    }
}
