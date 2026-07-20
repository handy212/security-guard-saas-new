<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\HasFormDrawer;
use App\Models\WebhookSubscription;
use App\Services\WebhookDeliveryService;
use App\Support\TenantContext;
use Livewire\Component;

class WebhookManager extends Component
{
    use AuthorizesModuleAccess, HasFormDrawer;

    public ?int $editingId = null;

    public string $event = 'incident.submitted';

    public string $targetUrl = '';

    public function mount(): void
    {
        $this->authorizePolicy('viewAny', WebhookSubscription::class);
    }

    public function openForm(): void
    {
        $this->editingId = null;
        $this->event = 'incident.submitted';
        $this->targetUrl = '';
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function closeDrawer(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->event = 'incident.submitted';
        $this->targetUrl = '';
        $this->resetErrorBag();
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

        $this->closeDrawer();
    }

    public function edit(int $id): void
    {
        $subscription = WebhookSubscription::findOrFail($id);
        $this->authorize('update', $subscription);
        $this->editingId = $subscription->id;
        $this->event = $subscription->event;
        $this->targetUrl = $subscription->target_url;
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function toggle(WebhookSubscription $subscription): void
    {
        $this->authorize('update', $subscription);
        $subscription->update(['is_active' => ! $subscription->is_active]);
    }

    public function delete(WebhookSubscription $subscription): void
    {
        $this->authorize('delete', $subscription);
        if ($this->editingId === $subscription->id) {
            $this->closeDrawer();
        }
        $subscription->delete();
        session()->flash('status', 'Webhook subscription deleted.');
    }

    public function render()
    {
        $subscriptions = WebhookSubscription::where('tenant_id', TenantContext::id())->latest()->get();

        return view('livewire.settings.webhook-manager', [
            'subscriptions' => $subscriptions,
            'stats' => [
                'total' => $subscriptions->count(),
                'active' => $subscriptions->where('is_active', true)->count(),
            ],
        ])->layout('layouts.app');
    }
}
