<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollExport extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'provider', 'period_start', 'period_end', 'file_path', 'exported_by_user_id', 'exported_at',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'exported_at' => 'datetime',
        ];
    }

    public function exportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exported_by_user_id');
    }
}
