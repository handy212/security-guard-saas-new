<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Api\Admin\StoreSiteRequest;
use App\Http\Requests\Api\Admin\UpdateSiteRequest;
use App\Models\Site;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Site::class);

        $query = Site::with('clientAccount')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where('name', 'like', $term)->orWhere('address', 'like', $term);
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(StoreSiteRequest $request): JsonResponse
    {
        $site = Site::create($request->validated() + ['tenant_id' => TenantContext::id()]);

        return $this->data($site->load('clientAccount'), 201);
    }

    public function show(Site $site): JsonResponse
    {
        $this->authorize('view', $site);

        return $this->data($site->load('clientAccount'));
    }

    public function update(UpdateSiteRequest $request, Site $site): JsonResponse
    {
        $site->update($request->validated());

        return $this->data($site->fresh()->load('clientAccount'));
    }

    public function destroy(Site $site): JsonResponse
    {
        $this->authorize('delete', $site);
        $site->delete();

        return $this->noContent();
    }
}
