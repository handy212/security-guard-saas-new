<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class GuardAvailability extends Model { use BelongsToTenant; protected $guarded=[]; public function guard(){return $this->belongsTo(Guard::class);} }
