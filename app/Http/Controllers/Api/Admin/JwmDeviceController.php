<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\JwmCheckpointMapping;
use App\Models\JwmDevice;
use App\Models\JwmDeviceAssignment;
use App\Models\JwmGuardMapping;
use App\Services\JwmDeviceManagementService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class JwmDeviceController extends AdminController
{
    public function __construct(private JwmDeviceManagementService $management) {}

    public function index(Request $request): JsonResponse
    {
        return $this->paginated(JwmDevice::where('tenant_id', TenantContext::id())
            ->latest('id')->paginate($this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        return $this->data($this->management->register(TenantContext::id(), $request->all()), 201);
    }

    public function update(Request $request, int $device): JsonResponse
    {
        return $this->data($this->management->configure(TenantContext::id(), $device, $request->all()));
    }

    public function assignments(Request $request, int $device): JsonResponse
    {
        JwmDevice::where('tenant_id', TenantContext::id())->findOrFail($device);

        return $this->paginated(JwmDeviceAssignment::where('tenant_id', TenantContext::id())
            ->where('jwm_device_id', $device)->latest('starts_at')->paginate($this->perPage($request)));
    }

    public function assign(Request $request, int $device): JsonResponse
    {
        return $this->data($this->management->assign(TenantContext::id(), $device, $request->all()), 201);
    }

    public function bridgeToken(Request $request, int $device): JsonResponse
    {
        return DB::transaction(function () use ($request, $device) {
            $registered = $this->lockedDevice($device);
            $this->requireActive($registered);
            if ($registered->bridge_token_id) {
                PersonalAccessToken::whereKey($registered->bridge_token_id)->delete();
            }
            $expiry = now()->addDays(30);
            $token = $request->user()->createToken("jwm-device-{$device}", ['jwm:ingest'], $expiry);
            $registered->update(['bridge_token_id' => $token->accessToken->id]);

            return $this->data([
                'token' => $token->plainTextToken, 'token_type' => 'Bearer', 'expires_at' => $expiry->toIso8601String(),
            ], 201)->header('Cache-Control', 'no-store');
        });
    }

    public function guardMappings(Request $request, int $device): JsonResponse
    {
        JwmDevice::where('tenant_id', TenantContext::id())->findOrFail($device);

        return $this->paginated(JwmGuardMapping::where('tenant_id', TenantContext::id())
            ->where('jwm_device_id', $device)->latest('id')->paginate($this->perPage($request)));
    }

    public function mapGuard(Request $request, int $device): JsonResponse
    {
        return $this->data($this->management->mapGuard(TenantContext::id(), $device, $request->all()), 201);
    }

    public function checkpointMappings(Request $request, int $device): JsonResponse
    {
        JwmDevice::where('tenant_id', TenantContext::id())->findOrFail($device);

        return $this->paginated(JwmCheckpointMapping::where('tenant_id', TenantContext::id())
            ->where('jwm_device_id', $device)->latest('id')->paginate($this->perPage($request)));
    }

    public function mapCheckpoint(Request $request, int $device): JsonResponse
    {
        return $this->data($this->management->mapCheckpoint(TenantContext::id(), $device, $request->all()), 201);
    }

    private function lockedDevice(int $id): JwmDevice
    {
        return JwmDevice::where('tenant_id', TenantContext::id())->lockForUpdate()->findOrFail($id);
    }

    private function requireActive(JwmDevice $device): void
    {
        if (! $device->is_active) {
            throw ValidationException::withMessages(['device' => 'This device is disabled.']);
        }
    }
}
