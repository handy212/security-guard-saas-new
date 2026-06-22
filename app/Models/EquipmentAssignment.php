<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class EquipmentAssignment extends Model { use BelongsToTenant; protected $guarded=[]; protected $casts=['issued_at'=>'datetime','returned_at'=>'datetime']; public function asset(){return $this->belongsTo(EquipmentAsset::class,'equipment_asset_id');} public function assignedGuard(){return $this->belongsTo(Guard::class);} public function site(){return $this->belongsTo(Site::class);} }
