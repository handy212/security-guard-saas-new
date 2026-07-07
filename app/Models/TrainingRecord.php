<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingRecord extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'guard_id', 'course_name', 'provider', 'completed_on', 'expires_on', 'certificate_path', 'status'];

    protected function casts(): array
    {
        return ['completed_on' => 'date', 'expires_on' => 'date'];
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class, 'guard_id');
    }
}
