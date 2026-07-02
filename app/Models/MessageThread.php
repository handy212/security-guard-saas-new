<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageThread extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'site_id', 'subject', 'type'];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MessageThreadParticipant::class);
    }
}
