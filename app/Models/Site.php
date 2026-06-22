<?php
namespace App\Models;
use App\Models\Concerns\BelongsToTenant; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class Site extends Model { use BelongsToTenant; protected $fillable=['tenant_id','client_account_id','name','address','latitude','longitude','geofence_radius_meters','status','instructions']; protected function casts(): array { return ['latitude'=>'float','longitude'=>'float']; } public function clientAccount(): BelongsTo { return $this->belongsTo(ClientAccount::class); } public function posts(): HasMany { return $this->hasMany(SitePost::class); } public function patrolRoutes(): HasMany { return $this->hasMany(PatrolRoute::class); } }
