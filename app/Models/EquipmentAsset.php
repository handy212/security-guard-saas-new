<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class EquipmentAsset extends Model { use BelongsToTenant; protected $guarded=[]; public function assignments(){return $this->hasMany(EquipmentAssignment::class);} }
