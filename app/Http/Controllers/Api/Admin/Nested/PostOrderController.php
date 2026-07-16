<?php

namespace App\Http\Controllers\Api\Admin\Nested;

use App\Models\PostOrder;
use App\Models\Site;
use App\Models\SitePost;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostOrderController extends NestedAdminController
{
    public function index(Request $request, Site $site): JsonResponse
    {
        $this->authorize('view', $site);

        $query = PostOrder::query()
            ->where('site_id', $site->id)
            ->with('sitePost')
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, Site $site): JsonResponse
    {
        $this->authorize('update', $site);

        $data = $this->validatedPostOrder($request);
        $this->ensureSitePostBelongsToSite($site, $data['site_post_id'] ?? null);

        $order = PostOrder::create([
            ...$data,
            'tenant_id' => TenantContext::id(),
            'site_id' => $site->id,
            'version' => 1,
        ]);

        return $this->data($order->load('sitePost'), 201);
    }

    public function show(Site $site, PostOrder $postOrder): JsonResponse
    {
        $this->authorize('view', $site);
        $this->belongsToSite($site, $postOrder);

        return $this->data($postOrder->load('sitePost'));
    }

    public function update(Request $request, Site $site, PostOrder $postOrder): JsonResponse
    {
        $this->authorize('update', $site);
        $this->belongsToSite($site, $postOrder);

        $data = $this->validatedPostOrder($request, partial: true);
        $this->ensureSitePostBelongsToSite($postOrder->site, $data['site_post_id'] ?? $postOrder->site_post_id);

        $postOrder->update($data);

        return $this->data($postOrder->fresh()->load('sitePost'));
    }

    public function destroy(Site $site, PostOrder $postOrder): JsonResponse
    {
        $this->authorize('update', $site);
        $this->belongsToSite($site, $postOrder);
        $postOrder->delete();

        return $this->noContent();
    }

    private function validatedPostOrder(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        $data = $request->validate([
            'site_post_id' => ['nullable', 'integer', TenantValidation::exists('site_posts')],
            'title' => [...$required, 'string', 'max:255'],
            'instructions' => [...$required, 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ]);

        if (array_key_exists('site_post_id', $data)) {
            $data['site_post_id'] = $data['site_post_id'] ?: null;
        }

        return $data;
    }

    private function ensureSitePostBelongsToSite(Site $site, ?int $sitePostId): void
    {
        if ($sitePostId === null) {
            return;
        }

        abort_unless(
            SitePost::query()->where('site_id', $site->id)->whereKey($sitePostId)->exists(),
            422,
            'The selected site post does not belong to this site.'
        );
    }
}
