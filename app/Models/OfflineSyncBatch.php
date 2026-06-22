<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class OfflineSyncBatch extends Model { use BelongsToTenant; protected $guarded=[]; protected $casts=['payload'=>'array','result'=>'array','processed_at'=>'datetime']; public function user(){return $this->belongsTo(User::class);} }
