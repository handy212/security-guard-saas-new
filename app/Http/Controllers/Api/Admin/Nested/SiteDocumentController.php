<?php

namespace App\Http\Controllers\Api\Admin\Nested;

use App\Models\Site;
use App\Models\SiteDocument;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteDocumentController extends NestedAdminController
{
    public function index(Request $request, Site $site): JsonResponse
    {
        $this->authorize('view', $site);

        $query = SiteDocument::query()
            ->where('site_id', $site->id)
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, Site $site): JsonResponse
    {
        $this->authorize('update', $site);

        $document = SiteDocument::create($this->validatedDocument($request) + [
            'tenant_id' => TenantContext::id(),
            'site_id' => $site->id,
        ]);

        return $this->data($document, 201);
    }

    public function show(Site $site, SiteDocument $document): JsonResponse
    {
        $this->authorize('view', $site);
        $this->belongsToSite($site, $document);

        return $this->data($document);
    }

    public function update(Request $request, Site $site, SiteDocument $document): JsonResponse
    {
        $this->authorize('update', $site);
        $this->belongsToSite($site, $document);

        $document->update($this->validatedDocument($request, partial: true));

        return $this->data($document->fresh());
    }

    public function destroy(Site $site, SiteDocument $document): JsonResponse
    {
        $this->authorize('update', $site);
        $this->belongsToSite($site, $document);
        $document->delete();

        return $this->noContent();
    }

    private function validatedDocument(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'title' => [...$required, 'string', 'max:255'],
            'document_type' => [...$required, 'string', 'max:80'],
            'expires_on' => ['nullable', 'date'],
            'client_visible' => ['boolean'],
            'file_path' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
