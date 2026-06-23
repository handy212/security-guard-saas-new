<?php

namespace App\Livewire\Settings;

use App\Models\WebhookSubscription;
use App\Services\WebhookDeliveryService;
use App\Support\TenantContext;
use Livewire\Component;

class WebhookManager extends Component
{
    public string $event = 'incident.submitted';

    public string $targetUrl = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
    }

    public function create(): void
    {
        $this->validate([
            'event' => 'required|string|max:120',
            'targetUrl' => 'required|url|max:255',
        ]);

        WebhookSubscription::create([
            'tenant_id' => TenantContext::id(),
            'event' => $this->event,
            'target_url' => $this->targetUrl,
            'secret' => WebhookDeliveryService::generateSecret(),
            'is_active' => true,
        ]);

        $this->reset(['targetUrl']);
        session()->flash('status', 'Webhook subscription created.');
    }

    public function toggle(WebhookSubscription $subscription): void
    {
        $subscription->update(['is_active' => ! $subscription->is_active]);
    }

    public function render()
    {
        return view('livewire.settings.webhook-manager', [
            'subscriptions' => WebhookSubscription::where('tenant_id', TenantContext::id())->latest()->get(),
        ])->layout('layouts.app');
    }
}
