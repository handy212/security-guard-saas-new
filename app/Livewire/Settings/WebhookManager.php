<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\WebhookSubscription;
use App\Services\WebhookDeliveryService;
use App\Support\TenantContext;
use Livewire\Component;

class WebhookManager extends Component
{
    use AuthorizesModuleAccess;

    public ?int $editingId = null;

    public string $event = 'incident.submitted';

    public string $targetUrl = '';

    public function mount(): void
    {
        $this->authorizePolicy('viewAny', WebhookSubscription::class);
    }

    public function create(): void
    {
        $this->validate([
            'event' => 'required|string|max:120',
            'targetUrl' => 'required|url|max:255',
        ]);

        if ($this->editingId) {
            $subscription = WebhookSubscription::findOrFail($this->editingId);
            $this->authorize('update', $subscription);
            $subscription->update([
                'event' => $this->event,
                'target_url' => $this->targetUrl,
            ]);
            session()->flash('status', 'Webhook subscription updated.');
        } else {
            $this->authorize('create', WebhookSubscription::class);
            WebhookSubscription::create([
                'tenant_id' => TenantContext::id(),
                'event' => $this->event,
                'target_url' => $this->targetUrl,
                'secret' => WebhookDeliveryService::generateSecret(),
                'is_active' => true,
            ]);
            session()->flash('status', 'Webhook subscription created.');
        }

        $this->cancelEdit();
    }

    public function edit(int $id): void
    {
        $subscription = WebhookSubscription::findOrFail($id);
        $this->authorize('update', $subscription);
        $this->editingId = $subscription->id;
        $this->event = $subscription->event;
        $this->targetUrl = $subscription->target_url;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->event = 'incident.submitted';
        $this->targetUrl = '';
    }

    public function toggle(WebhookSubscription $subscription): void
    {
        $this->authorize('update', $subscription);
        $subscription->update(['is_active' => ! $subscription->is_active]);
    }

    public function delete(WebhookSubscription $subscription): void
    {
        $this->authorize('delete', $subscription);
        $subscription->delete();
        session()->flash('status', 'Webhook subscription deleted.');
    }

    public function render()
    {
        return view('livewire.settings.webhook-manager', [
            'subscriptions' => WebhookSubscription::where('tenant_id', TenantContext::id())->latest()->get(),
        ])->layout('layouts.app');
    }
}
