<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
class ClientReportApproval extends Model { use BelongsToTenant; protected $guarded=[]; protected $casts=['approved_at'=>'datetime']; public function approvable(){return $this->morphTo();} public function clientAccount(){return $this->belongsTo(ClientAccount::class);} }
