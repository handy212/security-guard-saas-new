<?php

namespace App\Http\Controllers\Api\Admin\Nested;

use App\Models\Site;
use App\Models\SitePost;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SitePostController extends NestedAdminController
{
    public function index(Request $request, Site $site): JsonResponse
    {
        $this->authorize('view', $site);

        $query = SitePost::query()
            ->where('site_id', $site->id)
            ->orderBy('name');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, Site $site): JsonResponse
    {
        $this->authorize('update', $site);

        $post = SitePost::create($this->validatedPost($request) + [
            'tenant_id' => TenantContext::id(),
            'site_id' => $site->id,
        ]);

        return $this->data($post, 201);
    }

    public function show(Site $site, SitePost $post): JsonResponse
    {
        $this->authorize('view', $site);
        $this->belongsToSite($site, $post);

        return $this->data($post);
    }

    public function update(Request $request, Site $site, SitePost $post): JsonResponse
    {
        $this->authorize('update', $site);
        $this->belongsToSite($site, $post);

        $post->update($this->validatedPost($request, partial: true));

        return $this->data($post->fresh());
    }

    public function destroy(Site $site, SitePost $post): JsonResponse
    {
        $this->authorize('update', $site);
        $this->belongsToSite($site, $post);
        $post->delete();

        return $this->noContent();
    }

    private function validatedPost(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'name' => [...$required, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'required_guards' => ['integer', 'min:1'],
            'status' => [...$required, 'string', 'in:active,inactive'],
        ]);
    }
}
