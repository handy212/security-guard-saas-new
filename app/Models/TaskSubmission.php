<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskSubmission extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'checkpoint_scan_id', 'checkpoint_task_id', 'response', 'notes', 'media',
    ];

    protected function casts(): array
    {
        return ['media' => 'array'];
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(CheckpointScan::class, 'checkpoint_scan_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(CheckpointTask::class, 'checkpoint_task_id');
    }
}
