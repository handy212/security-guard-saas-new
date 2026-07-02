<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftTemplateItem extends Model
{
    protected $fillable = [
        'shift_template_id', 'day_of_week', 'start_time', 'end_time', 'site_id', 'required_guards', 'billing_rate',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'shift_template_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
