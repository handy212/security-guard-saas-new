<?php

namespace App\Models;

use App\Enums\GuardDutyType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardApplication extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'first_name', 'last_name', 'phone', 'email', 'duty_type', 'branch_id',
        'notes', 'photo_path', 'status', 'reviewed_by_user_id', 'reviewed_at', 'guard_id',
    ];

    protected function casts(): array
    {
        return [
            'duty_type' => GuardDutyType::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function dutyTypeLabel(): string
    {
        $type = $this->duty_type instanceof GuardDutyType
            ? $this->duty_type
            : GuardDutyType::tryFrom((string) $this->duty_type) ?? GuardDutyType::Guardian;

        return $type->label();
    }
}
