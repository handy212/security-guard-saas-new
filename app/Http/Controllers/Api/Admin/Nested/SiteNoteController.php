<?php

namespace App\Http\Controllers\Api\Admin\Nested;

use App\Models\Site;
use App\Models\SiteNote;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteNoteController extends NestedAdminController
{
    public function index(Request $request, Site $site): JsonResponse
    {
        $this->authorize('view', $site);

        $query = SiteNote::query()
            ->where('site_id', $site->id)
            ->with('author')
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, Site $site): JsonResponse
    {
        $this->authorize('update', $site);

        $note = SiteNote::create($this->validatedNote($request) + [
            'tenant_id' => TenantContext::id(),
            'site_id' => $site->id,
            'user_id' => $request->user()->id,
        ]);

        return $this->data($note->load('author'), 201);
    }

    public function show(Site $site, SiteNote $note): JsonResponse
    {
        $this->authorize('view', $site);
        $this->belongsToSite($site, $note);

        return $this->data($note->load('author'));
    }

    public function update(Request $request, Site $site, SiteNote $note): JsonResponse
    {
        $this->authorize('update', $site);
        $this->belongsToSite($site, $note);

        $note->update($this->validatedNote($request, partial: true));

        return $this->data($note->fresh()->load('author'));
    }

    public function destroy(Site $site, SiteNote $note): JsonResponse
    {
        $this->authorize('update', $site);
        $this->belongsToSite($site, $note);
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
