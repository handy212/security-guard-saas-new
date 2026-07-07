<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteReportSchedule extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'site_id', 'report_type', 'frequency', 'recipients', 'is_active', 'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'recipients' => 'array',
            'is_active' => 'boolean',
            'last_sent_at' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
