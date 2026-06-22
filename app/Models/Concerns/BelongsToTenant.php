<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        static::creating(function ($model) {
            if (empty($model->tenant_id) && app()->bound('currentTenant') && app('currentTenant')) {
                $model->tenant_id = app('currentTenant')->id;
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app()->bound('currentTenant') && app('currentTenant')) {
                $builder->where($builder->getModel()->getTable().'.tenant_id', app('currentTenant')->id);
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeForTenant(Builder $query, int|string $tenantId): Builder
    {
        return $query->withoutGlobalScope('tenant')->where('tenant_id', $tenantId);
    }
}
