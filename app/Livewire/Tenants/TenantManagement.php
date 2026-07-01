<?php

namespace App\Livewire\Tenants;

use App\Livewire\Concerns\HasFormDrawer;
use App\Livewire\Concerns\ManagesPlatform;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\PlanEntitlementService;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantManagement extends Component
{
    use HasFormDrawer, ManagesPlatform, WithPagination;

    public array $tenantForm = [
        'name' => '',
        'slug' => '',
        'subdomain' => '',
        'domain' => '',
        'status' => 'active',
        'trial_ends_at' => '',
        'plan_id' => '',
        'admin_name' => '',
        'admin_email' => '',
        'admin_password' => '',
    ];

    public array $inviteForm = [
        'name' => '',
        'email' => '',
        'password' => '',
    ];

    public ?int $resettingUserId = null;

    public string $resetPassword = '';

    public ?int $editingTenantId = null;

    public ?int $viewingTenantId = null;

    public bool $showDetail = false;

    public bool $showInviteForm = false;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $planFilter = 'all';

    public string $sortBy = 'name';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
        'planFilter' => ['except' => 'all', 'as' => 'plan'],
        'sortBy' => ['except' => 'name', 'as' => 'sort'],
    ];

    public function mount(): void
    {
        $this->ensurePlatformAdmin();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'planFilter', 'sortBy'], true)) {
            $this->resetPage();
        }
    }

    public function applyStatFilter(string $filter): void
    {
        $this->ensurePlatformAdmin();

        match ($filter) {
            'total' => [$this->statusFilter, $this->planFilter] = ['all', 'all'],
            'active' => [$this->statusFilter, $this->planFilter] = ['active', 'all'],
            'suspended' => [$this->statusFilter, $this->planFilter] = ['suspended', 'all'],
            'without_plan' => [$this->statusFilter, $this->planFilter] = ['all', 'none'],
            default => null,
        };

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->ensurePlatformAdmin();
        $this->search = '';
        $this->statusFilter = 'all';
        $this->planFilter = 'all';
        $this->sortBy = 'name';
        $this->resetPage();
    }

    public function openCreateTenant(): void
    {
        $this->ensurePlatformAdmin();
        $this->editingTenantId = null;
        $this->resetTenantForm();
        $this->showForm = true;
    }

    public function openViewTenant(int $tenantId): void
    {
        $this->ensurePlatformAdmin();
        $this->viewingTenantId = $tenantId;
        $this->showInviteForm = false;
        $this->resetInviteForm();
        $this->cancelResetPassword();
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->viewingTenantId = null;
        $this->showInviteForm = false;
        $this->resetInviteForm();
        $this->cancelResetPassword();
    }

    public function openEditTenant(int $tenantId): void
    {
        $this->ensurePlatformAdmin();

        $tenant = Tenant::findOrFail($tenantId);
        $this->editingTenantId = $tenant->id;
        $this->tenantForm = [
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'subdomain' => $tenant->subdomain ?? '',
            'domain' => $tenant->domain ?? '',
            'status' => $tenant->status ?? 'active',
            'trial_ends_at' => $tenant->trial_ends_at?->format('Y-m-d') ?? '',
            'plan_id' => (string) ($tenant->plan_id ?? $tenant->subscription?->subscription_plan_id ?? ''),
            'admin_name' => '',
            'admin_email' => '',
            'admin_password' => '',
        ];
        $this->showForm = true;
        $this->closeDetail();
    }

    public function saveTenant(AuditLogService $audit): void
    {
        $this->ensurePlatformAdmin();

        $rules = [
            'tenantForm.name' => 'required|string|max:255',
            'tenantForm.slug' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('tenants', 'slug')->ignore($this->editingTenantId),
            ],
            'tenantForm.subdomain' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('tenants', 'subdomain')->ignore($this->editingTenantId),
            ],
            'tenantForm.domain' => [
                'nullable', 'string', 'max:255',
                Rule::unique('tenants', 'domain')->ignore($this->editingTenantId),
            ],
            'tenantForm.status' => 'required|in:active,suspended',
            'tenantForm.trial_ends_at' => 'nullable|date',
            'tenantForm.plan_id' => 'nullable|exists:subscription_plans,id',
            'tenantForm.admin_name' => 'required_with:tenantForm.admin_email|nullable|string|max:255',
            'tenantForm.admin_email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'tenantForm.admin_password' => ['required_with:tenantForm.admin_email', 'nullable', 'string', Password::min(12)],
        ];

        if ($this->editingTenantId) {
            unset($rules['tenantForm.admin_name'], $rules['tenantForm.admin_email'], $rules['tenantForm.admin_password']);
        }

        $data = $this->validate($rules)['tenantForm'];

        $payload = [
            'name' => $data['name'],
            'slug' => $data['slug'],
            'subdomain' => $data['subdomain'] ?: $data['slug'],
            'domain' => $data['domain'] ?: null,
            'status' => $data['status'],
            'trial_ends_at' => $data['trial_ends_at'] ?: null,
            'plan_id' => $data['plan_id'] ?: null,
        ];

        if ($this->editingTenantId) {
            $tenant = Tenant::findOrFail($this->editingTenantId);
            $wasSuspended = $tenant->status === 'suspended';
            $tenant->update($payload);
            $this->syncTenantPlan($tenant, $data['plan_id'] ?: null);

            if ($payload['status'] === 'suspended' && ! $wasSuspended) {
                $this->clearTenantSession($tenant);
                $audit->recordPlatform('platform.tenant.suspended', $tenant, [], $tenant->id);
            } elseif ($payload['status'] === 'active' && $wasSuspended) {
                $audit->recordPlatform('platform.tenant.activated', $tenant, [], $tenant->id);
            } else {
                $audit->recordPlatform('platform.tenant.updated', $tenant, [], $tenant->id);
            }

            session()->flash('status', 'Tenant updated.');
        } else {
            $tenant = Tenant::create($payload);
            $this->syncTenantPlan($tenant, $data['plan_id'] ?: null);

            if (! empty($data['admin_email'])) {
                $user = User::create([
                    'tenant_id' => $tenant->id,
                    'name' => $data['admin_name'],
                    'email' => $data['admin_email'],
                    'password' => Hash::make($data['admin_password']),
                    'status' => 'active',
                ]);
                $user->assignRole('company-admin');
                $audit->recordPlatform('platform.tenant.admin_invited', $tenant, ['email' => $user->email], $tenant->id);
            }

            $audit->recordPlatform('platform.tenant.created', $tenant, [], $tenant->id);
            session()->flash('status', 'Tenant created successfully.');
        }

        $this->resetTenantForm();
        $this->closeDrawer();
    }

    public function inviteAdmin(int $tenantId, AuditLogService $audit): void
    {
        $this->ensurePlatformAdmin();

        $data = $this->validate([
            'inviteForm.name' => 'required|string|max:255',
            'inviteForm.email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'inviteForm.password' => ['required', 'string', Password::min(12)],
        ])['inviteForm'];

        $tenant = Tenant::findOrFail($tenantId);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => 'active',
        ]);
        $user->assignRole('company-admin');

        $audit->recordPlatform('platform.tenant.admin_invited', $tenant, ['email' => $user->email], $tenant->id);

        $this->showInviteForm = false;
        $this->resetInviteForm();
        session()->flash('status', 'Company admin invited.');
    }

    public function startResetPassword(int $userId): void
    {
        $this->ensurePlatformAdmin();
        $this->resettingUserId = $userId;
        $this->resetPassword = '';
        $this->resetValidation('resetPassword');
    }

    public function cancelResetPassword(): void
    {
        $this->resettingUserId = null;
        $this->resetPassword = '';
        $this->resetValidation('resetPassword');
    }

    public function resetAdminPassword(int $tenantId, AuditLogService $audit): void
    {
        $this->ensurePlatformAdmin();

        $data = $this->validate([
            'resettingUserId' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'resetPassword' => ['required', 'string', Password::min(12)],
        ]);

        $tenant = Tenant::findOrFail($tenantId);
        $user = User::findOrFail($data['resettingUserId']);
        $user->update(['password' => Hash::make($data['resetPassword'])]);

        $audit->recordPlatform('platform.tenant.password_reset', $tenant, [
            'email' => $user->email,
            'user_id' => $user->id,
        ], $tenant->id);

        $this->cancelResetPassword();
        session()->flash('status', "Password reset for {$user->name}.");
    }

    public function deleteTenant(int $tenantId, AuditLogService $audit): void
    {
        $this->ensurePlatformAdmin();

        $tenant = Tenant::withCount(['users', 'guards'])->findOrFail($tenantId);

        if ($tenant->users_count > 0 || $tenant->guards_count > 0) {
            $this->addError('tenant', 'Remove all users and guards before deleting this tenant.');

            return;
        }

        $this->clearTenantSession($tenant);
        $tenant->subscription()?->delete();
        $tenant->delete();
        $audit->recordPlatform('platform.tenant.deleted', null, ['slug' => $tenant->slug, 'name' => $tenant->name]);
        $this->closeDetail();
        session()->flash('status', 'Tenant deleted.');
    }

    public function enterTenant(int $tenantId, AuditLogService $audit)
    {
        $this->ensurePlatformAdmin();

        $tenant = Tenant::findOrFail($tenantId);
        TenantContext::enterTenant($tenant);
        $audit->recordPlatform('platform.tenant.entered', $tenant, [], $tenant->id);

        return redirect()->route('dashboard');
    }

    public function updateTenantStatus(int $tenantId, string $status, AuditLogService $audit): void
    {
        $this->ensurePlatformAdmin();
        abort_unless(in_array($status, ['active', 'suspended'], true), 422);

        $tenant = Tenant::findOrFail($tenantId);
        $tenant->update(['status' => $status]);

        if ($status === 'suspended') {
            $this->clearTenantSession($tenant);
            $audit->recordPlatform('platform.tenant.suspended', $tenant, [], $tenant->id);
        } else {
            $audit->recordPlatform('platform.tenant.activated', $tenant, [], $tenant->id);
        }

        session()->flash('status', $status === 'suspended' ? 'Tenant suspended.' : 'Tenant activated.');
    }

    public function assignTenantPlan(int $tenantId, int $planId, AuditLogService $audit): void
    {
        $this->ensurePlatformAdmin();

        $tenant = Tenant::findOrFail($tenantId);

        if ($planId === 0) {
            $tenant->update(['plan_id' => null]);
            $tenant->subscription()?->delete();
            $audit->recordPlatform('platform.tenant.plan_removed', $tenant, [], $tenant->id);
            session()->flash('status', 'Plan removed from tenant.');

            return;
        }

        abort_unless(SubscriptionPlan::whereKey($planId)->exists(), 422);

        $this->syncTenantPlan($tenant, (string) $planId);
        $audit->recordPlatform('platform.tenant.plan_assigned', $tenant, ['plan_id' => $planId], $tenant->id);
        session()->flash('status', 'Subscription plan updated.');
    }

    public function exportTenants(AuditLogService $audit): StreamedResponse
    {
        $this->ensurePlatformAdmin();

        $tenants = $this->sortedTenantsQuery()->get();

        $audit->recordPlatform('platform.tenant.exported', null, [
            'count' => $tenants->count(),
            'filters' => [
                'search' => $this->search,
                'status' => $this->statusFilter,
                'plan' => $this->planFilter,
                'sort' => $this->sortBy,
            ],
        ]);

        return response()->streamDownload(function () use ($tenants) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Name', 'Slug', 'Subdomain', 'Status', 'Plan', 'Trial ends', 'Users', 'Guards', 'Created']);

            foreach ($tenants as $tenant) {
                fputcsv($handle, [
                    $tenant->name,
                    $tenant->slug,
                    $tenant->subdomain,
                    $tenant->status,
                    $tenant->subscription?->plan?->name ?? '',
                    $tenant->trial_ends_at?->toDateString() ?? '',
                    $tenant->users_count,
                    $tenant->guards_count,
                    $tenant->created_at?->toDateString(),
                ]);
            }

            fclose($handle);
        }, 'tenants-'.now()->format('Y-m-d').'.csv');
    }

    public function updatedTenantFormName(string $value): void
    {
        if (! $this->editingTenantId && $this->tenantForm['slug'] === '') {
            $this->tenantForm['slug'] = Str::slug($value);
        }
    }

    public function render()
    {
        $this->ensurePlatformAdmin();

        $tenants = $this->sortedTenantsQuery()->paginate(25);

        $viewingTenant = $this->viewingTenantId
            ? Tenant::with(['subscription.plan', 'users' => fn ($q) => $q->select('id', 'tenant_id', 'name', 'email', 'status')->latest()->limit(5)])
                ->withCount(['users', 'guards'])
                ->find($this->viewingTenantId)
            : null;

        return view('livewire.tenants.tenant-management', [
            'tenants' => $tenants,
            'viewingTenant' => $viewingTenant,
            'tenantStats' => $this->tenantStats(),
            'plans' => SubscriptionPlan::where('status', 'active')->orderBy('name')->get(),
            'hasActiveFilters' => $this->search !== '' || $this->statusFilter !== 'all' || $this->planFilter !== 'all',
        ])->layout('layouts.app');
    }

    private function tenantsQuery(): Builder
    {
        return Tenant::query()
            ->with('subscription.plan')
            ->withCount(['users', 'guards'])
            ->when($this->search !== '', function (Builder $query) {
                $needle = '%'.$this->search.'%';

                $query->where(function (Builder $query) use ($needle) {
                    $query->where('name', 'like', $needle)
                        ->orWhere('slug', 'like', $needle)
                        ->orWhere('subdomain', 'like', $needle);
                });
            })
            ->when($this->statusFilter !== 'all', fn (Builder $query) => $query->where('status', $this->statusFilter))
            ->when($this->planFilter === 'none', fn (Builder $query) => $query
                ->whereNull('plan_id')
                ->whereDoesntHave('subscription'))
            ->when($this->planFilter !== 'all' && $this->planFilter !== 'none', function (Builder $query) {
                $planId = $this->planFilter;

                $query->where(function (Builder $query) use ($planId) {
                    $query->where('plan_id', $planId)
                        ->orWhereHas('subscription', fn (Builder $query) => $query->where('subscription_plan_id', $planId));
                });
            });
    }

    private function sortedTenantsQuery(): Builder
    {
        $query = $this->tenantsQuery();

        return match ($this->sortBy) {
            'created' => $query->orderByDesc('created_at'),
            'users' => $query->orderByDesc('users_count')->orderBy('name'),
            default => $query->orderBy('name'),
        };
    }

    private function tenantStats(): array
    {
        return [
            'total' => Tenant::count(),
            'active' => Tenant::where('status', 'active')->count(),
            'suspended' => Tenant::where('status', 'suspended')->count(),
            'without_plan' => Tenant::query()
                ->whereNull('plan_id')
                ->whereDoesntHave('subscription')
                ->count(),
        ];
    }

    private function syncTenantPlan(Tenant $tenant, ?string $planId): void
    {
        if (! $planId) {
            $tenant->update(['plan_id' => null]);
            $tenant->subscription()?->delete();

            return;
        }

        $plan = SubscriptionPlan::findOrFail($planId);

        $tenant->update(['plan_id' => $plan->id]);

        TenantSubscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'subscription_plan_id' => $plan->id,
                'status' => $tenant->trial_ends_at && $tenant->trial_ends_at->isFuture() ? 'trial' : 'active',
                'starts_at' => now(),
                'trial_ends_at' => $tenant->trial_ends_at,
            ]
        );

        app(PlanEntitlementService::class)->syncBillingLimits($tenant, $plan);
    }

    private function clearTenantSession(Tenant $tenant): void
    {
        if (TenantContext::switchedTenantSlug() === $tenant->slug) {
            TenantContext::exitTenant();
        }
    }

    private function resetTenantForm(): void
    {
        $this->editingTenantId = null;
        $this->tenantForm = [
            'name' => '',
            'slug' => '',
            'subdomain' => '',
            'domain' => '',
            'status' => 'active',
            'trial_ends_at' => '',
            'plan_id' => '',
            'admin_name' => '',
            'admin_email' => '',
            'admin_password' => '',
        ];
    }

    private function resetInviteForm(): void
    {
        $this->inviteForm = [
            'name' => '',
            'email' => '',
            'password' => '',
        ];
    }
}
