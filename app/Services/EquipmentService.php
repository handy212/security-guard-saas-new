<?php

namespace App\Services;

use App\Models\EquipmentAsset;
use App\Models\EquipmentAssignment;

/** @deprecated Use AssetManagementService */
class EquipmentService
{
    public function __construct(private AssetManagementService $assets) {}

    public function issue(EquipmentAsset $asset, array $data): EquipmentAssignment
    {
        return $this->assets->issue($asset, $data);
    }

    public function returnAsset(EquipmentAssignment $assignment, ?string $notes = null): EquipmentAssignment
    {
        return $this->assets->returnAsset($assignment, $notes);
    }
}
