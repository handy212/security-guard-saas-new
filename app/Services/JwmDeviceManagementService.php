<?php

namespace App\Services;

use App\Models\JwmCheckpointMapping;
use App\Models\JwmDevice;
use App\Models\JwmDeviceAssignment;
use App\Models\JwmGuardMapping;
use App\Rules\JwmExternalId;
use App\Rules\JwmTimestamp;
use App\Support\TenantValidation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class JwmDeviceManagementService
{
    public function register(int $tenantId, array $input): JwmDevice
    {
        $data = Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'reader_number' => ['required', 'string', 'max:100', Rule::unique('jwm_devices')->where('tenant_id', $tenantId)],
        ])->validate();

        return JwmDevice::create($data + ['tenant_id' => $tenantId])->refresh();
    }

    public function configure(int $tenantId, int $deviceId, array $input): JwmDevice
    {
        $data = Validator::make($input, [
            'is_active' => ['required_without:require_device_identity', 'boolean'],
            'require_device_identity' => ['required_without:is_active', 'boolean'],
        ])->validate();

        return DB::transaction(function () use ($tenantId, $deviceId, $data) {
            $device = $this->lockedDevice($tenantId, $deviceId);
            if (isset($data['is_active']) && ! $data['is_active'] && $device->bridge_token_id) {
                PersonalAccessToken::whereKey($device->bridge_token_id)->delete();
                $device->bridge_token_id = null;
            }
            $device->fill($data)->save();

            return $device;
        });
    }

    public function assign(int $tenantId, int $deviceId, array $input): JwmDeviceAssignment
    {
        $data = Validator::make($input, [
            'guard_id' => ['required', 'integer', TenantValidation::exists('guards')],
            'starts_at' => ['required', new JwmTimestamp],
            'ends_at' => ['required', new JwmTimestamp, 'after:starts_at'],
        ])->validate();

        return DB::transaction(function () use ($tenantId, $deviceId, $data) {
            $device = $this->lockedDevice($tenantId, $deviceId);
            $this->requireActive($device);
            $start = CarbonImmutable::parse($data['starts_at'])->utc();
            $end = CarbonImmutable::parse($data['ends_at'])->utc();
            if (JwmDeviceAssignment::where('tenant_id', $tenantId)->where('jwm_device_id', $deviceId)
                ->where('starts_at', '<', $end)->where('ends_at', '>', $start)->exists()) {
                throw ValidationException::withMessages(['starts_at' => 'This device is already assigned during that interval.']);
            }

            return JwmDeviceAssignment::create([
                'tenant_id' => $tenantId, 'jwm_device_id' => $deviceId, 'guard_id' => $data['guard_id'],
                'starts_at' => $start, 'ends_at' => $end,
            ]);
        });
    }

    public function mapGuard(int $tenantId, int $deviceId, array $input): JwmGuardMapping
    {
        $data = Validator::make($input, [
            'external_id' => ['required', new JwmExternalId],
            'guard_id' => ['required', 'integer', TenantValidation::exists('guards')],
        ])->validate();

        return DB::transaction(function () use ($tenantId, $deviceId, $data) {
            $device = $this->lockedDevice($tenantId, $deviceId);
            $this->requireActive($device);
            $hash = hash('sha256', $data['external_id']);
            if (JwmGuardMapping::where('tenant_id', $tenantId)->where('jwm_device_id', $deviceId)
                ->where('external_id_hash', $hash)->exists()) {
                throw ValidationException::withMessages(['external_id' => 'This external guard ID is already mapped and cannot be rebound.']);
            }

            return JwmGuardMapping::create($data + [
                'tenant_id' => $tenantId, 'jwm_device_id' => $deviceId, 'external_id_hash' => $hash,
            ]);
        });
    }

    public function mapCheckpoint(int $tenantId, int $deviceId, array $input): JwmCheckpointMapping
    {
        $data = Validator::make($input, [
            'external_id' => ['required', new JwmExternalId],
            'patrol_checkpoint_id' => ['required', 'integer', TenantValidation::exists('patrol_checkpoints')],
        ])->validate();

        return DB::transaction(function () use ($tenantId, $deviceId, $data) {
            $device = $this->lockedDevice($tenantId, $deviceId);
            $this->requireActive($device);
            $hash = hash('sha256', $data['external_id']);
            if (JwmCheckpointMapping::where('tenant_id', $tenantId)->where('jwm_device_id', $deviceId)
                ->where('external_id_hash', $hash)->exists()) {
                throw ValidationException::withMessages(['external_id' => 'This external checkpoint ID is already mapped and cannot be rebound.']);
            }

            return JwmCheckpointMapping::create($data + [
                'tenant_id' => $tenantId, 'jwm_device_id' => $deviceId, 'external_id_hash' => $hash,
            ]);
        });
    }

    private function lockedDevice(int $tenantId, int $deviceId): JwmDevice
    {
        return JwmDevice::where('tenant_id', $tenantId)->lockForUpdate()->findOrFail($deviceId);
    }

    private function requireActive(JwmDevice $device): void
    {
        if (! $device->is_active) {
            throw ValidationException::withMessages(['device' => 'This device is disabled.']);
        }
    }
}
