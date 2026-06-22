<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class VisitorLog extends Model { use BelongsToTenant; protected $guarded=[]; protected $casts=['checked_in_at'=>'datetime','checked_out_at'=>'datetime','metadata'=>'array']; public function site(){return $this->belongsTo(Site::class);} public function guard(){return $this->belongsTo(Guard::class);} }
