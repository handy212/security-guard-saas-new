<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientNote extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'client_account_id', 'user_id', 'body', 'is_internal',
    ];

    protected function casts(): array
    {
        return ['is_internal' => 'boolean'];
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
