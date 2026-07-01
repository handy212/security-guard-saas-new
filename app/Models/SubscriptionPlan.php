<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name', 'slug', 'paystack_plan_code', 'monthly_price', 'annual_price',
        'max_guards', 'max_sites', 'features', 'status',
    ];

    protected function casts(): array
    {
        return ['features' => 'array'];
    }

    public function subscriptions()
    {
        return $this->hasMany(TenantSubscription::class);
    }
}
