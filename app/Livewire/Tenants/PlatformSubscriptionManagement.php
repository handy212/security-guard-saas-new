<?php

namespace App\Livewire\Tenants;

use App\Livewire\Concerns\HasFormDrawer;
use App\Livewire\Concerns\ManagesPlatform;
use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use App\Services\AuditLogService;
use App\Services\PlanEntitlementService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PlatformSubscriptionManagement extends Component
{
    use HasFormDrawer, ManagesPlatform, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public ?int $editingId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['as' => 'status', 'except' => 'all'],
    ];

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
        $sub = $this->currentSubscriptions()->findOrFail($id);
        $this->editingId = $sub->id;
        $this->form = [
            'subscription_plan_id' => (string) $sub->subscription_plan_id,
            'status' => $sub->status,
            'trial_ends_at' => $sub->trial_ends_at?->format('Y-m-d') ?? '',
        ];
        $this->showForm = true;
    }

    public function save(PlanEntitlementService $entitlements, AuditLogService $audit): void
    {
        $this->ensurePlatformAdmin();

        $data = $this->validate([
            'form.subscription_plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'form.status' => 'required|in:active,trial,cancelled,past_due',
            'form.trial_ends_at' => 'nullable|date',
        ])['form'];

        DB::transaction(function () use ($data, $entitlements, $audit): void {
            $sub = $this->currentSubscriptions()->lockForUpdate()->findOrFail($this->editingId);
            $plan = SubscriptionPlan::findOrFail($data['subscription_plan_id']);
            abort_unless($plan->status === 'active' || $plan->id === $sub->subscription_plan_id, 422);
            $trialEndsAt = $data['trial_ends_at'] ?: null;
            $sub->update([
                'subscription_plan_id' => $plan->id,
                'status' => $data['status'],
                'trial_ends_at' => $trialEndsAt,
            ]);
            $tenant = $sub->tenant;
            $tenant->update(['plan_id' => $plan->id, 'trial_ends_at' => $trialEndsAt]);
            $entitlements->syncBillingLimits($tenant, $plan);
            $audit->recordPlatform('platform.subscription.updated', $sub, ['plan_id' => $plan->id, 'status' => $sub->status], $tenant->id);
        });

        session()->flash('status', 'Subscription updated.');
        $this->closeDrawer();
        $this->editingId = null;
    }

    public function render()
    {
        $this->ensurePlatformAdmin();

        $current = $this->currentSubscriptions();
        $statusCounts = (clone $current)->selectRaw('status, COUNT(*) AS total')->groupBy('status')->pluck('total', 'status');
        $subscriptions = (clone $current)
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
            'plans' => SubscriptionPlan::query()
                ->where(fn (Builder $query) => $query->where('status', 'active')->orWhere('id', $this->form['subscription_plan_id'] ?: null))
                ->orderBy('name')->get(),
            'stats' => [
                'total' => $statusCounts->sum(),
                'active' => $statusCounts['active'] ?? 0,
                'trial' => $statusCounts['trial'] ?? 0,
                'past_due' => $statusCounts['past_due'] ?? 0,
            ],
        ])->layout('layouts.app');
    }

    private function currentSubscriptions(): Builder
    {
        return TenantSubscription::query()->whereIn('id', TenantSubscription::query()->selectRaw('MAX(id)')->groupBy('tenant_id'));
    }
}
