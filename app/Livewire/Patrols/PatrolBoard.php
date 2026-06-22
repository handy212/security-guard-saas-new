<?php

namespace App\Livewire\Patrols;

use App\Models\PatrolCheckpoint;
use App\Models\PatrolRoute;
use App\Models\PatrolSession;
use App\Models\Site;
use App\Support\TenantContext;
use Livewire\Component;

class PatrolBoard extends Component
{
    public string $search = '';

    public array $routeForm = ['site_id' => '', 'name' => '', 'description' => '', 'expected_duration_minutes' => 30, 'status' => 'active'];

    public array $checkpointForm = ['patrol_route_id' => '', 'name' => '', 'code' => '', 'sequence' => 1, 'latitude' => '', 'longitude' => ''];

    public function saveRoute(): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
        $data = $this->validate([
            'routeForm.site_id' => 'required',
            'routeForm.name' => 'required',
            'routeForm.expected_duration_minutes' => 'integer',
        ])['routeForm'];
        PatrolRoute::create($data + ['tenant_id' => TenantContext::id()]);
    }

    public function saveCheckpoint(): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
        $data = $this->validate([
            'checkpointForm.patrol_route_id' => 'required',
            'checkpointForm.name' => 'required',
            'checkpointForm.code' => 'required',
            'checkpointForm.sequence' => 'integer',
        ])['checkpointForm'];
        PatrolCheckpoint::create($data + ['tenant_id' => TenantContext::id()]);
    }

    public function render()
    {
        return view('livewire.patrols.patrol-board', [
            'routes' => PatrolRoute::with(['site', 'checkpoints'])->latest()->get(),
            'sessions' => PatrolSession::with(['route', 'assignedGuard', 'scans'])->latest()->limit(20)->get(),
            'sites' => Site::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
