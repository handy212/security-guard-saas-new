<?php

namespace App\Http\Controllers\Api\Admin\Nested;

use App\Models\CheckpointTask;
use App\Models\PatrolCheckpoint;
use App\Models\Site;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckpointTaskController extends NestedAdminController
{
    public function index(Request $request, Site $site): JsonResponse
    {
        $this->authorize('view', $site);

        $query = CheckpointTask::query()
            ->where('tenant_id', TenantContext::id())
            ->whereHas('checkpoint.route', fn ($q) => $q->where('site_id', $site->id))
            ->with('checkpoint.route')
            ->orderBy('sort_order');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, Site $site): JsonResponse
    {
        $this->authorize('update', $site);

        $data = $this->validatedTask($request);
        $this->ensureCheckpointBelongsToSite($site, $data['patrol_checkpoint_id']);

        $task = CheckpointTask::create($data + [
            'tenant_id' => TenantContext::id(),
            'sort_order' => $request->integer('sort_order', 1),
        ]);

        return $this->data($task->load('checkpoint.route'), 201);
    }

    public function show(Site $site, CheckpointTask $checkpointTask): JsonResponse
    {
        $resolved = $this->siteForTask($checkpointTask);
        abort_unless((int) $resolved->id === (int) $site->id, 404);
        $this->authorize('view', $site);

        return $this->data($checkpointTask->load('checkpoint.route'));
    }

    public function update(Request $request, Site $site, CheckpointTask $checkpointTask): JsonResponse
    {
        $resolved = $this->siteForTask($checkpointTask);
        abort_unless((int) $resolved->id === (int) $site->id, 404);
        $this->authorize('update', $site);

        $data = $this->validatedTask($request, partial: true);

        if (isset($data['patrol_checkpoint_id'])) {
            $this->ensureCheckpointBelongsToSite($site, $data['patrol_checkpoint_id']);
        }

        $checkpointTask->update($data);

        return $this->data($checkpointTask->fresh()->load('checkpoint.route'));
    }

    public function destroy(Site $site, CheckpointTask $checkpointTask): JsonResponse
    {
        $resolved = $this->siteForTask($checkpointTask);
        abort_unless((int) $resolved->id === (int) $site->id, 404);
        $this->authorize('update', $site);
        $checkpointTask->delete();

        return $this->noContent();
    }

    private function siteForTask(CheckpointTask $task): Site
    {
        $task->loadMissing('checkpoint.route.site');
        $site = $task->checkpoint?->route?->site;
        abort_unless($site, 404);

        return $site;
    }

    private function ensureCheckpointBelongsToSite(Site $site, int $checkpointId): void
    {
        abort_unless(
            PatrolCheckpoint::query()
                ->whereKey($checkpointId)
                ->whereHas('route', fn ($q) => $q->where('site_id', $site->id))
                ->exists(),
            422,
            'The selected checkpoint does not belong to this site.'
        );
    }

    private function validatedTask(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'patrol_checkpoint_id' => [...$required, 'integer', TenantValidation::exists('patrol_checkpoints')],
            'title' => [...$required, 'string', 'max:255'],
            'response_type' => [...$required, 'string', 'in:yes_no,text,number,photo'],
            'is_required' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);
    }
}
