<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
class ClientAccount extends Model { use BelongsToTenant; protected $fillable=['tenant_id','name','industry','email','phone','address','status','default_hourly_rate']; public function sites(): HasMany { return $this->hasMany(Site::class); } public function invoices(): HasMany { return $this->hasMany(Invoice::class); } }
