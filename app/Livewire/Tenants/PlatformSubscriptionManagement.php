<?php

namespace App\Livewire\Tenants;

use App\Livewire\Concerns\HasFormDrawer;
use App\Livewire\Concerns\ManagesPlatform;
use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class PlatformSubscriptionManagement extends Component
{
    use HasFormDrawer, ManagesPlatform, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public ?int $editingId = null;

    public array $form = [
        'subscription_plan_id' => '',
        'status' => 'active',
        'trial_ends_at' => '',
    ];

    public function mount(): void
    {
        $this->ensurePlatformAdmin();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function openEdit(int $id): void
    {
        $this->ensurePlatformAdmin();
        $sub = TenantSubscription::with('tenant')->findOrFail($id);
        $this->editingId = $sub->id;
        $this->form = [
            'subscription_plan_id' => (string) $sub->subscription_plan_id,
            'status' => $sub->status,
            'trial_ends_at' => $sub->trial_ends_at?->format('Y-m-d') ?? '',
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->ensurePlatformAdmin();

        $data = $this->validate([
            'form.subscription_plan_id' => 'required|exists:subscription_plans,id',
            'form.status' => 'required|in:active,trial,cancelled,past_due',
            'form.trial_ends_at' => 'nullable|date',
        ])['form'];

        $sub = TenantSubscription::with('tenant')->findOrFail($this->editingId);
        $sub->update([
            'subscription_plan_id' => $data['subscription_plan_id'],
            'status' => $data['status'],
            'trial_ends_at' => $data['trial_ends_at'] ?: null,
        ]);

        $sub->tenant?->update(['plan_id' => $data['subscription_plan_id']]);

        session()->flash('status', 'Subscription updated.');
        $this->closeDrawer();
        $this->editingId = null;
    }

    public function render()
    {
        $this->ensurePlatformAdmin();

        $subscriptions = TenantSubscription::query()
            ->with(['tenant', 'plan'])
            ->when($this->search !== '', function ($q) {
                $needle = '%'.$this->search.'%';
                $q->whereHas('tenant', fn ($q) => $q->where('name', 'like', $needle)->orWhere('slug', 'like', $needle));
            })
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(25);

        return view('livewire.tenants.platform-subscription-management', [
            'subscriptions' => $subscriptions,
            'plans' => SubscriptionPlan::where('status', 'active')->orderBy('name')->get(),
            'stats' => [
                'total' => TenantSubscription::count(),
                'active' => TenantSubscription::where('status', 'active')->count(),
                'trial' => TenantSubscription::where('status', 'trial')->count(),
            ],
        ])->layout('layouts.app');
    }
}
