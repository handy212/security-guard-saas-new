<?php

namespace App\Livewire\Passdown;

use App\Models\PassdownLog;
use App\Models\Site;
use App\Models\SitePost;
use App\Support\TenantContext;
use Livewire\Component;

class PassdownIndex extends Component
{
    public array $form = ['site_id' => '', 'site_post_id' => '', 'content' => ''];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.site_id' => 'required',
            'form.content' => 'required|string|min:10',
        ])['form'];

        $guardId = auth()->user()->guardProfile?->id;

        PassdownLog::create($data + [
            'tenant_id' => TenantContext::id(),
            'guard_id' => $guardId ?? \App\Models\Guard::where('tenant_id', TenantContext::id())->value('id'),
        ]);

        $this->form = ['site_id' => '', 'site_post_id' => '', 'content' => ''];
        session()->flash('status', 'Passdown log saved.');
    }

    public function render()
    {
        return view('livewire.passdown.passdown-index', [
            'logs' => PassdownLog::with(['site', 'sitePost', 'assignedGuard'])->where('tenant_id', TenantContext::id())->latest()->limit(30)->get(),
            'sites' => Site::orderBy('name')->get(),
            'posts' => SitePost::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
