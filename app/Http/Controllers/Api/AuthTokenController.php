<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthTokenController extends Controller
{
    private const ADMIN_PERMISSIONS = [
        'dashboard.view', 'clients.manage', 'sites.manage', 'guards.manage', 'schedules.manage',
        'attendance.manage', 'patrols.manage', 'incidents.manage', 'reports.approve', 'dispatch.manage',
        'billing.manage', 'payroll.manage', 'settings.manage', 'audit.view', 'analytics.view',
        'compliance.manage', 'equipment.manage', 'visitors.manage', 'exports.manage',
    ];

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->status !== 'active') {
            throw ValidationException::withMessages([
                'email' => ['This account is inactive.'],
            ]);
        }

        if (! $user->tenant_id) {
            throw ValidationException::withMessages([
                'email' => ['Admin API tokens require a tenant-scoped user account.'],
            ]);
        }

        setPermissionsTeamId($user->tenant_id);

        if (! $this->hasAdminAccess($user)) {
            throw ValidationException::withMessages([
                'email' => ['This account does not have admin API access.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $token = $user->createToken($data['device_name'])->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'tenant_id' => $user->tenant_id,
                ],
            ],
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(null, 204);
    }

    private function hasAdminAccess(User $user): bool
    {
        foreach (self::ADMIN_PERMISSIONS as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }
}
