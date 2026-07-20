<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\HasFormDrawer;
use App\Models\NotificationTemplate;
use App\Support\TenantContext;
use Livewire\Component;

class NotificationTemplateIndex extends Component
{
    use AuthorizesModuleAccess, HasFormDrawer;

    public ?int $editingId = null;

    public array $form = [
        'code' => '',
        'channel' => 'mail',
        'subject' => '',
        'body' => '',
        'is_active' => true,
    ];

    public function mount(): void
    {
        $this->authorizePermission('settings.manage');
    }

    public function openForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function closeDrawer(): void
    {
        $this->resetForm();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);

        $data = $this->validate([
            'form.code' => 'required|string|max:120',
            'form.channel' => 'required|in:mail,sms,database',
            'form.subject' => 'nullable|string|max:255',
            'form.body' => 'required|string|max:5000',
            'form.is_active' => 'boolean',
        ])['form'];

        if ($this->editingId) {
            $template = NotificationTemplate::where('tenant_id', TenantContext::id())->findOrFail($this->editingId);
            $template->update($data);
            session()->flash('status', 'Template updated.');
        } else {
            NotificationTemplate::create($data + ['tenant_id' => TenantContext::id()]);
            session()->flash('status', 'Template created.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $template = NotificationTemplate::where('tenant_id', TenantContext::id())->findOrFail($id);
        $this->editingId = $template->id;
        $this->form = [
            'code' => $template->code,
            'channel' => $template->channel,
            'subject' => $template->subject ?? '',
            'body' => $template->body,
            'is_active' => (bool) $template->is_active,
        ];
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function toggle(int $id): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $template = NotificationTemplate::where('tenant_id', TenantContext::id())->findOrFail($id);
        $template->update(['is_active' => ! $template->is_active]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $template = NotificationTemplate::where('tenant_id', TenantContext::id())->findOrFail($id);
        $template->delete();
        if ($this->editingId === $id) {
            $this->resetForm();
        }
        session()->flash('status', 'Template deleted.');
    }

    public function render()
    {
        $templates = NotificationTemplate::where('tenant_id', TenantContext::id())->orderBy('code')->get();

        return view('livewire.settings.notification-template-index', [
            'templates' => $templates,
            'stats' => [
                'total' => $templates->count(),
                'active' => $templates->where('is_active', true)->count(),
                'mail' => $templates->where('channel', 'mail')->count(),
            ],
            'suggestedCodes' => [
                'incident.submitted',
                'sos.raised',
                'compliance.expiring',
                'patrol.missed',
                'geofence.violation',
                'guard.idle',
                'shift.confirmed',
                'report.delivered',
            ],
        ])->layout('layouts.app');
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->form = [
            'code' => '',
            'channel' => 'mail',
            'subject' => '',
            'body' => '',
            'is_active' => true,
        ];
        $this->resetErrorBag();
    }
}
