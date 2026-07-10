<?php

namespace App\Livewire\Guards;

use App\Enums\GuardDutyType;
use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\Branch;
use App\Models\Guard;
use App\Models\GuardApplication;
use App\Models\Tenant;
use App\Services\PlanLimitService;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class GuardApplicationQueue extends Component
{
    use AuthorizesModuleAccess, WithPagination;

    public string $search = '';

    public string $statusFilter = 'pending';

    public function mount(): void
    {
        $this->authorizePermission('guards.manage');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function approve(int $id, PlanLimitService $limits): void
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);

        $application = GuardApplication::findOrFail($id);
        abort_unless($application->status === 'pending', 422);

        $tenant = Tenant::findOrFail(TenantContext::id());
        abort_unless($limits->canCreateGuard($tenant), 403, 'Guard limit reached for your plan.');

        $guard = Guard::create([
            'tenant_id' => $application->tenant_id,
            'first_name' => $application->first_name,
            'last_name' => $application->last_name,
            'phone' => $application->phone,
            'email' => $application->email,
            'duty_type' => $application->duty_type instanceof GuardDutyType
                ? $application->duty_type->value
                : $application->duty_type,
            'branch_id' => $application->branch_id,
            'photo_path' => $application->photo_path,
            'status' => 'inactive',
            'verification_status' => 'unverified',
            'monthly_rate' => 0,
        ]);

        $application->update([
            'status' => 'approved',
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => now(),
            'guard_id' => $guard->id,
        ]);

        session()->flash('status', $application->full_name.' approved and added to the roster.');
        $this->redirect(route('guards.show', $guard), navigate: true);
    }

    public function reject(int $id): void
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);

        $application = GuardApplication::findOrFail($id);
        abort_unless($application->status === 'pending', 422);

        $application->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        session()->flash('status', 'Application rejected.');
    }

    public function render()
    {
        $tenant = TenantContext::current();

        return view('livewire.guards.guard-application-queue', [
            'applications' => GuardApplication::query()
                ->with('branch')
                ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
                ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('first_name', 'like', '%'.$this->search.'%')
                        ->orWhere('last_name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%')
                        ->orWhere('phone', 'like', '%'.$this->search.'%');
                }))
                ->latest()
                ->paginate(15),
            'publicApplyUrl' => $tenant?->slug ? url('/apply/'.$tenant->slug) : null,
            'branches' => Branch::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
