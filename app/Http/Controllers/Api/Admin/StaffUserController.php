<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\User;
use App\Services\TenantRoleProvisioner;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class StaffUserController extends AdminController
{
    private const HIDDEN_ROLES = ['super-admin'];

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = $this->staffQuery()
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name');

        $paginator = $query->paginate($this->perPage($request));
        $paginator->getCollection()->transform(fn (User $user) => $this->staffPayload($user));

        return $this->paginated($paginator);
    }

    public function store(Request $request, TenantRoleProvisioner $roles): JsonResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->where(fn ($q) => $q->where('tenant_id', TenantContext::id())),
            ],
            'password' => ['required', 'string', Password::min(12)],
            'role' => ['required', 'string', Rule::notIn(self::HIDDEN_ROLES)],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        $this->assertRoleAllowed($data['role']);

        $user = User::create([
            'tenant_id' => TenantContext::id(),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => $data['status'] ?? 'active',
        ]);

        $roles->assignRole($user, $data['role']);

        return $this->data($this->staffPayload($user->fresh()), 201);
    }

    public function show(User $staffUser): JsonResponse
    {
        $this->authorize('view', $staffUser);

        return $this->data($this->staffPayload($staffUser));
    }

    public function update(Request $request, User $staffUser, TenantRoleProvisioner $roles): JsonResponse
    {
        $this->authorize('update', $staffUser);

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => [
                'sometimes', 'required', 'email', 'max:255',
                Rule::unique('users', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', TenantContext::id()))
                    ->ignore($staffUser->id),
            ],
            'password' => ['nullable', 'string', Password::min(12)],
            'role' => ['sometimes', 'required', 'string', Rule::notIn(self::HIDDEN_ROLES)],
            'status' => ['nullable', 'in:active,inactive'],
        ]);

        if (isset($data['role'])) {
            $this->assertRoleAllowed($data['role']);
        }

        $password = $data['password'] ?? null;
        unset($data['password'], $data['role']);

        if ($password) {
            $data['password'] = Hash::make($password);
        }

        $staffUser->update($data);

        if ($request->filled('role')) {
            setPermissionsTeamId($staffUser->tenant_id);
            $staffUser->syncRoles([$request->string('role')]);
        }

        return $this->data($this->staffPayload($staffUser->fresh()));
    }

    public function destroy(User $staffUser): JsonResponse
    {
        $this->authorize('delete', $staffUser);

        if ($staffUser->id === auth()->id()) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 422);
        }

        $staffUser->update(['status' => 'inactive']);

        return $this->noContent();
    }

    private function staffQuery()
    {
        return User::query()
            ->where('tenant_id', TenantContext::id())
            ->whereNull('client_account_id');
    }

    private function staffPayload(User $user): array
    {
        setPermissionsTeamId($user->tenant_id);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'roles' => $user->getRoleNames()->values()->all(),
            'last_login_at' => $user->last_login_at,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    private function assertRoleAllowed(string $roleName): void
    {
        abort_unless(
            Role::query()
                ->where('tenant_id', TenantContext::id())
                ->where('name', $roleName)
                ->whereNotIn('name', self::HIDDEN_ROLES)
                ->exists(),
            422,
            'Invalid role for this tenant.'
        );
    }
}
