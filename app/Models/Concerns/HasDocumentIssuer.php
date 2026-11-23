<?php

namespace App\Models\Concerns;

use App\Services\CompanyProfileService;

trait HasDocumentIssuer
{
    protected static function bootHasDocumentIssuer(): void
    {
        static::saving(function (self $document): void {
            if ($document->issuer_profile === null
                && $document->isDirty('status')
                && in_array($document->status, ['sent', 'accepted', 'converted', 'partial', 'paid', 'overdue'], true)
                && (! $document->exists || $document->getRawOriginal('status') === 'draft')) {
                $document->issuer_profile = $document->documentIssuer();
            }
        });
    }

    public function documentIssuer(): array
    {
        if ($this->issuer_profile !== null) {
            return $this->issuer_profile;
        }

        $tenant = $this->tenant;
        if (! $tenant || ($this->exists && $this->getRawOriginal('status') !== 'draft')) {
            return ['name' => $tenant?->name ?? config('app.name')];
        }

        return app(CompanyProfileService::class)->forTenant($tenant);
    }
}
