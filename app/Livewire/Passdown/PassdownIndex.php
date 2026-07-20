<?php

namespace App\Livewire\Passdown;

use App\Livewire\Concerns\HasFormDrawer;
use App\Models\Guard;
use App\Models\PassdownLog;
use App\Models\Site;
use App\Models\SitePost;
use App\Services\PassdownService;
use App\Support\TenantContext;
use Livewire\Component;

class PassdownIndex extends Component
{
    use HasFormDrawer;

    public ?int $editingId = null;

    public array $form = ['site_id' => '', 'site_post_id' => '', 'content' => ''];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
    }

    public function openForm(): void
    {
        $this->editingId = null;
        $this->form = ['site_id' => '', 'site_post_id' => '', 'content' => ''];
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $log = PassdownLog::findOrFail($id);
        abort_unless((int) $log->tenant_id === (int) TenantContext::id(), 404);

        $this->editingId = $log->id;
        $this->form = [
            'site_id' => (string) $log->site_id,
            'site_post_id' => (string) ($log->site_post_id ?? ''),
            'content' => $log->content,
        ];
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function closeDrawer(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->form = ['site_id' => '', 'site_post_id' => '', 'content' => ''];
        $this->resetErrorBag();
    }

    public function save(PassdownService $service): void
    {
        $data = $this->validate([
            'form.site_id' => 'required',
            'form.site_post_id' => 'nullable',
            'form.content' => 'required|string|min:10',
        ])['form'];
        $data['site_post_id'] = $data['site_post_id'] ?: null;

        if ($this->editingId) {
            $log = PassdownLog::findOrFail($this->editingId);
            $service->update($log, $data);
            session()->flash('status', 'Passdown updated.');
        } else {
            $guardId = auth()->user()->guardProfile?->id
                ?? Guard::where('tenant_id', TenantContext::id())->value('id');
            $service->create($data + ['guard_id' => $guardId]);
            session()->flash('status', 'Passdown log saved.');
        }

        $this->closeDrawer();
    }

    public function delete(int $id, PassdownService $service): void
    {
        $log = PassdownLog::findOrFail($id);
        abort_unless((int) $log->tenant_id === (int) TenantContext::id(), 404);
        $service->delete($log);
        session()->flash('status', 'Passdown deleted.');
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $logs = PassdownLog::with(['site', 'sitePost', 'assignedGuard'])
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit(30)
            ->get();

        return view('livewire.passdown.passdown-index', [
            'logs' => $logs,
            'sites' => Site::where('tenant_id', $tenantId)->orderBy('name')->get(),
            'posts' => SitePost::where('tenant_id', $tenantId)->orderBy('name')->get(),
            'stats' => [
                'total' => $logs->count(),
                'sites' => $logs->pluck('site_id')->filter()->unique()->count(),
                'today' => $logs->filter(fn ($l) => $l->created_at?->isToday())->count(),
            ],
        ])->layout('layouts.app');
    }
}
