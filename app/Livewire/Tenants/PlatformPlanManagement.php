<?php

namespace App\Livewire\Tenants;

use App\Livewire\Concerns\HasFormDrawer;
use App\Livewire\Concerns\ManagesPlatform;
use App\Models\BillingLimit;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\PlanEntitlementService;
use App\Services\PlanLimitService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PlatformPlanManagement extends Component
{
    use HasFormDrawer, ManagesPlatform;

    public ?int $editingPlanId = null;

    public string $search = '';

    public string $statusFilter = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['as' => 'status', 'except' => 'all'],
    ];

    public array $form = [
        'name' => '',
        'slug' => '',
        'paystack_plan_code' => '',
        'monthly_price' => 0,
        'annual_price' => 0,
        'max_guards' => '',
        'max_sites' => '',
        'selectedFeatures' => [],
        'status' => 'active',
    ];

    public function mount(): void
    {
        $this->ensurePlatformAdmin();
    }

    public function openCreate(): void
    {
        $this->ensurePlatformAdmin();
        $this->editingPlanId = null;
        $this->resetForm();
        $this->showForm = true;
    }

    public function openEdit(int $planId): void
    {
        $this->ensurePlatformAdmin();
        $plan = SubscriptionPlan::findOrFail($planId);
        $this->editingPlanId = $plan->id;
        $this->form = [
            'name' => $plan->name,
            'slug' => $plan->slug,
            'paystack_plan_code' => $plan->paystack_plan_code ?? '',
            'monthly_price' => $plan->monthly_price,
            'annual_price' => $plan->annual_price,
            'max_guards' => $plan->max_guards ?? '',
            'max_sites' => $plan->max_sites ?? '',
            'selectedFeatures' => app(PlanEntitlementService::class)->featuresForPlan($plan),
            'status' => $plan->status,
        ];
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->ensurePlatformAdmin();

        $catalogKeys = app(PlanEntitlementService::class)->catalogKeys();
        if (blank($this->form['slug'] ?? null) && is_string($this->form['name'] ?? null)) {
            $this->form['slug'] = Str::slug($this->form['name']);
        }

        $data = $this->validate([
            'form.name' => 'required|string|max:255',
            'form.slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('subscription_plans', 'slug')->ignore($this->editingPlanId)],
            'form.paystack_plan_code' => 'nullable|string|max:255',
            'form.monthly_price' => 'required|numeric|min:0',
            'form.annual_price' => 'required|numeric|min:0',
            'form.max_guards' => 'nullable|integer|min:0|max:2147483647',
            'form.max_sites' => 'nullable|integer|min:0|max:2147483647',
            'form.selectedFeatures' => 'array',
            'form.selectedFeatures.*' => ['string', Rule::in($catalogKeys)],
            'form.status' => 'required|in:active,inactive',
        ])['form'];

        $payload = [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'paystack_plan_code' => $data['paystack_plan_code'] ?: null,
            'monthly_price' => $data['monthly_price'],
            'annual_price' => $data['annual_price'],
            'max_guards' => filled($data['max_guards']) ? (int) $data['max_guards'] : null,
            'max_sites' => filled($data['max_sites']) ? (int) $data['max_sites'] : null,
            'features' => array_values(array_unique($data['selectedFeatures'] ?? [])),
            'status' => $data['status'],
        ];

        if ($this->editingPlanId) {
            DB::transaction(function () use ($payload): void {
                $plan = SubscriptionPlan::query()->lockForUpdate()->findOrFail($this->editingPlanId);
                $plan->update($payload);
                if ($plan->wasChanged(['max_guards', 'max_sites'])) {
                    $tenantIds = Tenant::query()->select('id')
                        ->whereHas('subscription', fn (Builder $query) => $query->where('subscription_plan_id', $plan->id))
                        ->orWhere(fn (Builder $query) => $query->whereDoesntHave('subscription')->where('plan_id', $plan->id));
                    BillingLimit::query()->whereIn('tenant_id', $tenantIds)->update([
                        'max_guards' => $plan->max_guards ?? PlanLimitService::DEFAULT_MAX_GUARDS,
                        'max_sites' => $plan->max_sites ?? PlanLimitService::DEFAULT_MAX_SITES,
                    ]);
                }
            });
            session()->flash('status', 'Plan updated.');
        } else {
            SubscriptionPlan::create($payload);
            session()->flash('status', 'Plan created.');
        }

        $this->resetForm();
        $this->closeDrawer();
    }

    public function delete(int $planId): void
    {
        $this->ensurePlatformAdmin();

        $plan = SubscriptionPlan::withCount(['subscriptions', 'tenants'])->findOrFail($planId);

        if ($plan->subscriptions_count > 0 || $plan->tenants_count > 0) {
            $this->addError('plan', 'This plan is assigned to tenants. Reassign them before deleting.');

            return;
        }

        $plan->delete();
        session()->flash('status', 'Plan deleted.');
    }

    public function render()
    {
        $this->ensurePlatformAdmin();

        $entitlements = app(PlanEntitlementService::class);

        return view('livewire.tenants.platform-plan-management', [
            'plans' => SubscriptionPlan::query()
                ->withCount(['subscriptions', 'tenants'])
                ->when($this->search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')->orWhere('slug', 'like', '%'.$this->search.'%')))
                ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
                ->orderBy('monthly_price')
                ->get(),
            'planStats' => [
                'total' => SubscriptionPlan::count(),
                'active' => SubscriptionPlan::where('status', 'active')->count(),
                'assigned' => SubscriptionPlan::has('subscriptions')->orHas('tenants')->count(),
            ],
            'featureGroups' => $entitlements->groupedCatalog(),
            'featureLabels' => $entitlements->catalog(),
            'defaultFeatures' => $entitlements->featuresForPlan(null),
        ])->layout('layouts.app');
    }

    private function resetForm(): void
    {
        $this->editingPlanId = null;
        $this->form = [
            'name' => '', 'slug' => '', 'paystack_plan_code' => '',
            'monthly_price' => 0, 'annual_price' => 0,
            'max_guards' => '', 'max_sites' => '',
            'selectedFeatures' => app(PlanEntitlementService::class)->featuresForPlan(null), 'status' => 'active',
        ];
    }
}
