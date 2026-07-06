<?php

namespace App\Models;

use App\Enums\GuardDocumentType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardDocument extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'guard_id', 'type', 'file_path', 'expires_at', 'status',
    ];

    protected function casts(): array
    {
        return ['expires_at' => 'date'];
    }

    public function assignedGuard(): BelongsTo
    {
        return $this->belongsTo(Guard::class);
    }

    public function typeLabel(): string
    {
        return GuardDocumentType::tryFrom($this->type)?->label()
            ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function isPreviewable(): bool
    {
        return $this->isImagePreview() || $this->isPdfPreview();
    }

    public function isImagePreview(): bool
    {
        $extension = strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }

    public function isPdfPreview(): bool
    {
        return strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION)) === 'pdf';
    }
}
