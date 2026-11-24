<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class PatriotAccountMapping extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'instance', 'account_reference', 'account_number', 'billing_reference',
        'site_reference', 'site_id', 'verification_status', 'synchronized_at', 'response_details',
    ];

    protected $hidden = ['response_details'];

    protected function casts(): array
    {
        return ['synchronized_at' => 'datetime', 'response_details' => 'encrypted:array'];
    }
}
