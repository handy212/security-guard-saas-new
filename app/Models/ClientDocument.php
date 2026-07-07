<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientDocument extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'client_account_id', 'title', 'document_type', 'file_path', 'expires_on', 'client_visible',
    ];

    protected function casts(): array
    {
        return [
            'expires_on' => 'date',
            'client_visible' => 'boolean',
        ];
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }
}
