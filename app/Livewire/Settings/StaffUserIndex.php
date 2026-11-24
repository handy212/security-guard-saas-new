<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\HasFormDrawer;
use App\Livewire\Concerns\HasPerPage;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\TenantRoleProvisioner;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class StaffUserIndex extends Component
{
    use HasFormDrawer, HasPerPage, WithPagination;

    private const HIDDEN_ROLES = ['super-admin', 'client'];

    public string $search = '';

    public ?int $editingId = null;

    public array $form = [
        'name' => '',
        'email' => '',
        'password' => '',
        'role' => '',
        'status' => 'active',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        abort_if(TenantContext::isPlatformConsole(), 403);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);
        $user = $this->staffQuery()->findOrFail($id);

        $this->editingId = $user->id;
        $this->form = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'role' => $this->userRoleName($user),
            'status' => $user->status,
        ];
        $this->showForm = true;
    }

    public function save(AuditLogService $audit, TenantRoleProvisioner $provisioner): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);

        $tenantId = TenantContext::id();
        $user = $this->editingId ? $this->staffQuery()->findOrFail($this->editingId) : null;
        $uniqueEmail = Rule::unique('users', 'email');
        if ($user) {
            $uniqueEmail->ignore($user);
        }

        $rules = [
            'form.name' => 'required|string|max:255',
            'form.email' => [
                'required', 'email', 'max:255',
                $uniqueEmail,
            ],
            'form.role' => ['required', Rule::in($this->assignableRoleNames())],
            'form.status' => 'required|in:active,inactive',
        ];

        if ($this->editingId) {
            $rules['form.password'] = ['nullable', 'string', Password::min(12)];
        } else {
            $rules['form.password'] = ['required', 'string', Password::min(12)];
        }

        $data = $this->validate($rules)['form'];

        if ($user) {
            if ((int) $user->id === (int) auth()->id() && $data['status'] !== 'active') {
                $this->addError('form.status', 'You cannot deactivate your own account.');

                return;
            }

            if ($this->removesOnlyActiveAdmin($user, $data['status'], $data['role'])) {
                $this->addError('form.role', 'Keep an active company administrator in the tenant.');

                return;
            }

            $payload = [
                'name' => $data['name'],
                'email' => $data['email'],
                'status' => $data['status'],
            ];

            if (filled($data['password'] ?? null)) {
                $payload['password'] = Hash::make($data['password']);
            }

            DB::transaction(function () use ($user, $payload, $data, $provisioner): void {
                $user->update($payload);
                $this->syncUserRole($user, $data['role'], $provisioner);
                if ($payload['status'] === 'inactive' || isset($payload['password'])) {
                    $user->tokens()->delete();
                }
            });

            $audit->record('settings.staff.updated', $user, ['email' => $user->email, 'role' => $data['role']]);
            session()->flash('status', "Staff user {$user->name} updated.");
        } else {
            $user = User::create([
                'tenant_id' => $tenantId,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'status' => $data['status'],
            ]);

            $provisioner->assignRole($user, $data['role']);

            $audit->record('settings.staff.created', $user, ['email' => $user->email, 'role' => $data['role']]);
            session()->flash('status', "Staff user {$user->email} created.");
        }

        $this->resetForm();
        $this->showForm = false;
    }

    public function deactivate(int $id, AuditLogService $audit): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);

        $user = $this->staffQuery()->findOrFail($id);
        abort_if((int) $user->id === (int) auth()->id(), 403, 'You cannot deactivate your own account.');
        abort_if($this->removesOnlyActiveAdmin($user, 'inactive', $this->userRoleName($user)), 422, 'Keep an active company administrator in the tenant.');

        DB::transaction(function () use ($user): void {
            $user->update(['status' => 'inactive']);
            $user->tokens()->delete();
        });
        $audit->record('settings.staff.deactivated', $user, ['email' => $user->email]);
        session()->flash('status', "{$user->name} deactivated.");
    }

    public function reactivate(int $id, AuditLogService $audit): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);

        $user = $this->staffQuery()->findOrFail($id);
        $user->update(['status' => 'active']);
        $audit->record('settings.staff.reactivated', $user, ['email' => $user->email]);
        session()->flash('status', "{$user->name} reactivated.");
    }

    public function delete(int $id, AuditLogService $audit): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);

        $user = $this->staffQuery()->findOrFail($id);
        abort_if((int) $user->id === (int) auth()->id(), 403, 'You cannot delete your own account.');
        abort_if($user->guardProfile()->exists(), 403, 'This user is linked to a guard profile. Deactivate instead.');
        abort_if($user->status === 'active', 403, 'Deactivate this user before deleting.');
        abort_if($this->removesOnlyActiveAdmin($user, 'inactive', $this->userRoleName($user)), 422, 'Keep an active company administrator in the tenant.');

        $audit->record('settings.staff.deleted', $user, ['email' => $user->email]);
        $user->delete();
        session()->flash('status', 'Staff user deleted.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        setPermissionsTeamId($tenantId);

        $users = $this->staffQuery()
            ->with('guardProfile')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('name')
            ->paginate($this->resolvedPerPage());

        return view('livewire.settings.staff-user-index', [
            'users' => $users,
            'activeCount' => $this->staffQuery()->where('status', 'active')->count(),
            'inactiveCount' => $this->staffQuery()->where('status', 'inactive')->count(),
            'roles' => $this->assignableRoles(),
            'roleLabels' => collect($this->assignableRoles())->mapWithKeys(fn (Role $role) => [
                $role->name => str($role->name)->headline()->toString(),
            ]),
        ])->layout('layouts.app');
    }

    private function staffQuery()
    {
        return User::query()
            ->where('tenant_id', TenantContext::id())
            ->whereNull('client_account_id');
    }

    private function assignableRoles()
    {
        return Role::query()
            ->where('tenant_id', TenantContext::id())
            ->whereNotIn('name', self::HIDDEN_ROLES)
            ->orderBy('name')
            ->get();
    }

    /** @return list<string> */
    private function assignableRoleNames(): array
    {
        return $this->assignableRoles()->pluck('name')->all();
    }

    private function userRoleName(User $user): string
    {
        setPermissionsTeamId($user->tenant_id);

        return $user->getRoleNames()
            ->reject(fn (string $name) => in_array($name, self::HIDDEN_ROLES, true))
            ->first() ?? '';
    }

    private function syncUserRole(User $user, string $roleName, TenantRoleProvisioner $provisioner): void
    {
        setPermissionsTeamId($user->tenant_id);
        $provisioner->provision($user->tenant);
        $user->syncRoles([$roleName]);
    }

    private function removesOnlyActiveAdmin(User $user, string $status, string $role): bool
    {
        if ($status === 'active' && $role === 'company-admin') {
            return false;
        }

        if ($user->status !== 'active' || ! $user->hasRole('company-admin')) {
            return false;
        }

        return ! $this->staffQuery()
            ->whereKeyNot($user->id)
            ->where('status', 'active')
            ->whereHas('roles', fn ($query) => $query->where('name', 'company-admin'))
            ->exists();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'name' => '',
            'email' => '',
            'password' => '',
            'role' => '',
            'status' => 'active',
        ];
    }
}
