<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class JwmDevice extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'name', 'reader_number', 'model', 'is_active', 'bridge_token_id', 'require_device_identity'];

    protected $hidden = ['bridge_token_id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'require_device_identity' => 'boolean'];
    }
}
