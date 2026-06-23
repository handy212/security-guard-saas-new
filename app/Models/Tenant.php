<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'domain', 'subdomain', 'status', 'plan_id', 'trial_ends_at',
        'stripe_customer_id', 'stripe_subscription_id',
    ];

    protected function casts(): array
    {
        return ['trial_ends_at' => 'datetime'];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
