<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'client_account_id', 'name', 'description', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function clientAccount(): BelongsTo
    {
        return $this->belongsTo(ClientAccount::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(ReportTemplateField::class)->orderBy('sort_order');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ReportTemplateAssignment::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(CustomReportSubmission::class);
    }
}
