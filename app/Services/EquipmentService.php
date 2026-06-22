<?php
namespace App\Services;
use App\Models\EquipmentAsset; use App\Models\EquipmentAssignment;
class EquipmentService {
    public function issue(EquipmentAsset $asset, array $data): EquipmentAssignment { $asset->update(['status'=>'issued']); return EquipmentAssignment::create(array_merge($data,['equipment_asset_id'=>$asset->id,'issued_at'=>now(),'status'=>'issued'])); }
    public function returnAsset(EquipmentAssignment $assignment, ?string $notes=null): EquipmentAssignment { $assignment->update(['returned_at'=>now(),'return_notes'=>$notes,'status'=>'returned']); $assignment->asset?->update(['status'=>'available']); return $assignment; }
}
