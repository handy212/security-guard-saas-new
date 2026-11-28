<?php

namespace App\Services;

use App\Enums\AssetStatus;
use App\Enums\GuardDutyType;
use App\Enums\VehicleStatus;
use App\Models\DispatchTeam;
use App\Models\EquipmentAsset;
use App\Models\FleetVehicle;
use App\Models\ResponseDeployment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ResponseDeploymentService
{
    public function __construct(private readonly AssetManagementService $assets) {}

    public function deploy(int $tenantId, int $teamId, int $actorId, array $data): ResponseDeployment
    {
        return DB::transaction(function () use ($tenantId, $teamId, $actorId, $data): ResponseDeployment {
            $team = DispatchTeam::forTenant($tenantId)->lockForUpdate()->findOrFail($teamId);
            $point = $team->standbyPoint;
            if (! $point?->region?->is_active || $point->tenant_id !== $tenantId || ! $point->radius_meters
                || $team->activeDeployment()->exists() || $team->outstandingDispatches()->exists()) {
                throw new RuntimeException('Choose an idle team with an active standby point and a configured radius.');
            }
            $officers = $team->members()->where('tenant_id', $tenantId)->where('status', 'active')
                ->where('duty_type', GuardDutyType::Dispatch->value)->whereIn('id', $data['officers'])
                ->lockForUpdate()->pluck('id')->all();
            if (count($officers) !== count($data['officers']) || ! in_array((int) $data['custodian'], $officers, true)) {
                throw new RuntimeException('Select active officers from the team and choose a duty lead.');
            }

            $deployment = ResponseDeployment::create([
                'tenant_id' => $tenantId, 'dispatch_team_id' => $team->id, 'standby_point_id' => $point->id,
                'standby_location' => $point->only('name', 'latitude', 'longitude', 'radius_meters'),
                'officer_ids' => $officers, 'custodian_id' => $data['custodian'],
                'handover_location' => $data['location'], 'planned_end_at' => $data['end'],
                'deployed_by' => $actorId,
            ]);
            $kit = collect($data['kit'] ?? [])->groupBy('asset_id')->sortKeys();
            foreach ($kit as $assetId => $items) {
                $asset = EquipmentAsset::forTenant($tenantId)->with('assetCategory')->lockForUpdate()->findOrFail($assetId);
                if ($items->map(fn (array $item): int => (int) $item['guard_id'])->unique()->count() !== $items->count()) {
                    throw new RuntimeException($asset->displayLabel().' is listed more than once for the same officer. Use one checkout line per officer.');
                }
                $quantity = $items->sum('quantity');
                if ($asset->assetCategory?->isConsumable()) {
                    if ($quantity > $asset->quantity_on_hand) {
                        throw new RuntimeException($asset->displayLabel().' has only '.$asset->quantity_on_hand.' available across all checkout lines.');
                    }
                } elseif ($quantity !== 1 || $items->count() !== 1) {
                    throw new RuntimeException($asset->displayLabel().' can only be issued once to one officer.');
                }
                if ($asset->fleet_vehicle_id) {
                    $vehicle = FleetVehicle::forTenant($tenantId)->lockForUpdate()->findOrFail($asset->fleet_vehicle_id);
                    if ($vehicle->status !== VehicleStatus::AVAILABLE) {
                        throw new RuntimeException($asset->displayLabel().' is linked to a vehicle that is already in use or unavailable.');
                    }
                }
                foreach ($items as $item) {
                    $holderId = (int) $item['guard_id'];
                    if (! in_array($holderId, $officers, true)) {
                        throw new RuntimeException('Assign each kit item to an officer reporting for this duty.');
                    }
                    $this->assets->issue($asset, [
                        'guard_id' => $holderId, 'quantity' => $item['quantity'],
                        'issue_notes' => $item['condition'], 'response_deployment_id' => $deployment->id,
                    ]);
                }
            }

            return $deployment;
        }, attempts: 3);
    }

    public function arrive(ResponseDeployment $deployment, int $actorId, ?int $guardId = null, ?float $lat = null, ?float $lng = null, ?string $override = null): void
    {
        DB::transaction(function () use ($deployment, $actorId, $guardId, $lat, $lng, $override): void {
            DispatchTeam::forTenant($deployment->tenant_id)->lockForUpdate()->findOrFail($deployment->dispatch_team_id);
            $current = ResponseDeployment::forTenant($deployment->tenant_id)->lockForUpdate()->findOrFail($deployment->id);
            if ($current->closed_at || $current->ending_at || $current->arrived_at) {
                throw new RuntimeException('This deployment is no longer awaiting arrival.');
            }
            if ($override === null) {
                if ($guardId !== $current->custodian_id || $lat === null || $lng === null) {
                    throw new RuntimeException('The duty lead must confirm arrival with GPS.');
                }
                $point = (object) $current->standby_location;
                $point->geofence_radius_meters = $point->radius_meters;
                if (! app(AttendanceService::class)->withinGeofence($point, $lat, $lng)) {
                    throw new RuntimeException('You are outside the standby point radius.');
                }
            }
            $current->update(['arrived_at' => now(), 'arrived_by' => $actorId, 'arrival_override' => $override]);
        }, attempts: 3);
    }

    public function close(ResponseDeployment $deployment, int $actorId, array $returns): void
    {
        DB::transaction(function () use ($deployment, $actorId, $returns): void {
            $team = DispatchTeam::forTenant($deployment->tenant_id)->lockForUpdate()->findOrFail($deployment->dispatch_team_id);
            $current = ResponseDeployment::forTenant($deployment->tenant_id)->lockForUpdate()->findOrFail($deployment->id);
            if ($current->closed_at || ! $current->ending_at || $team->outstandingDispatches()->exists()) {
                throw new RuntimeException('Complete the outstanding dispatch and confirm return before closing duty.');
            }
            foreach ($current->kit()->where('status', 'issued')->orderBy('equipment_asset_id')->lockForUpdate()->get() as $assignment) {
                $return = $returns[$assignment->id] ?? [];
                $outcome = $return['outcome'] ?? null;
                $notes = trim($return['notes'] ?? '');
                $asset = EquipmentAsset::forTenant($current->tenant_id)->lockForUpdate()->findOrFail($assignment->equipment_asset_id);
                $consumable = $asset->assetCategory?->isConsumable() ?? false;
                if (! in_array($outcome, $consumable ? ['returned', 'consumed'] : ['returned', 'damaged', 'missing'], true)
                    || ($outcome !== 'returned' && $notes === '')) {
                    throw new RuntimeException('Record an outcome for every kit item and add notes for exceptions.');
                }
                if ($outcome === 'returned') {
                    $this->assets->returnAsset($assignment, $notes, $current->id);

                    continue;
                }
                $assignment->update(['status' => $outcome, 'return_notes' => $notes, 'returned_at' => now()]);
                if (! $consumable) {
                    $asset->update(['status' => $outcome === 'damaged' ? AssetStatus::MAINTENANCE : AssetStatus::LOST]);
                    if ($asset->fleet_vehicle_id) {
                        FleetVehicle::forTenant($current->tenant_id)->whereKey($asset->fleet_vehicle_id)->update(['status' => VehicleStatus::MAINTENANCE]);
                    }
                }
            }
            $current->update(['closed_at' => now(), 'closed_by' => $actorId]);
        }, attempts: 3);
    }

    public function beginHandover(ResponseDeployment $deployment): void
    {
        DB::transaction(function () use ($deployment): void {
            $team = DispatchTeam::forTenant($deployment->tenant_id)->lockForUpdate()->findOrFail($deployment->dispatch_team_id);
            $current = ResponseDeployment::forTenant($deployment->tenant_id)->lockForUpdate()->findOrFail($deployment->id);
            if ($current->closed_at || $team->outstandingDispatches()->exists()) {
                throw new RuntimeException('Complete the outstanding dispatch and confirm return before ending duty.');
            }
            if (! $current->ending_at) {
                $current->update(['ending_at' => now()]);
            }
        }, attempts: 3);
    }
}
