<?php

namespace App\Models;

use App\Enums\GuardDutyType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guard extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'employee_number', 'first_name', 'last_name', 'phone', 'email',
        'photo_path', 'rank', 'duty_type', 'branch_id', 'status', 'verification_status', 'verified_at',
        'verified_by_user_id', 'show_current_assignment', 'monthly_rate', 'license_number',
        'license_expires_at', 'emergency_contact_name', 'emergency_contact_phone', 'hire_date', 'settings',
    ];

    protected $appends = ['full_name'];

    protected function casts(): array
    {
        return [
            'license_expires_at' => 'date',
            'verified_at' => 'datetime',
            'show_current_assignment' => 'boolean',
            'hire_date' => 'date',
            'settings' => 'array',
            'duty_type' => GuardDutyType::class,
            'monthly_rate' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
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

    public function documents(): HasMany
    {
        return $this->hasMany(GuardDocument::class);
    }

    public function certifications(): HasMany
    {
        return $this->hasMany(GuardCertification::class);
    }

    public function skills(): HasMany
    {
        return $this->hasMany(GuardSkill::class);
    }

    public function trainingRecords(): HasMany
    {
        return $this->hasMany(TrainingRecord::class);
    }

    public function disciplinaryRecords(): HasMany
    {
        return $this->hasMany(DisciplinaryRecord::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(GuardAvailability::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(GuardNote::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(GuardReminder::class);
    }

    public function siteAssignments(): HasMany
    {
        return $this->hasMany(GuardSiteAssignment::class);
    }

    public function resolvedSettings(): array
    {
        return array_merge(
            config('guard_profile.default_settings', []),
            $this->settings ?? [],
        );
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function openShiftBids(): HasMany
    {
        return $this->hasMany(OpenShiftBid::class);
    }

    public function attendanceLogs(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function verificationTokens(): HasMany
    {
        return $this->hasMany(GuardVerificationToken::class);
    }

    public function activeVerificationToken(): ?GuardVerificationToken
    {
        return $this->verificationTokens()
            ->whereNull('revoked_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();
    }
}
