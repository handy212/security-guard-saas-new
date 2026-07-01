<?php

namespace App\Services;

use App\Models\Guard;
use App\Models\GuardVerificationToken;
use Illuminate\Support\Str;

class GuardVerificationService
{
    public function verificationUrl(GuardVerificationToken $token): string
    {
        return url('/g/'.$token->token);
    }

    public function issueToken(Guard $guard): GuardVerificationToken
    {
        if ($guard->verification_status !== 'verified') {
            throw new \InvalidArgumentException('Guard must be verified before issuing a QR token.');
        }

        GuardVerificationToken::query()
            ->where('guard_id', $guard->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        $ttlDays = config('guard_verification.token_ttl_days', 365);

        return GuardVerificationToken::create([
            'tenant_id' => $guard->tenant_id,
            'guard_id' => $guard->id,
            'token' => $this->generateUniqueToken(),
            'expires_at' => $ttlDays > 0 ? now()->addDays($ttlDays) : null,
        ]);
    }

    public function revokeActiveTokens(Guard $guard): void
    {
        GuardVerificationToken::query()
            ->where('guard_id', $guard->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function findValidToken(string $token): ?GuardVerificationToken
    {
        $record = GuardVerificationToken::query()
            ->where('token', $token)
            ->with(['assignedGuard.branch', 'assignedGuard.certifications', 'assignedGuard.skills', 'assignedGuard.tenant'])
            ->first();

        if (! $record || ! $record->isValid()) {
            return null;
        }

        $guard = $record->assignedGuard;

        if (! $guard || $guard->verification_status !== 'verified') {
            return null;
        }

        return $record;
    }

    public function recordScan(GuardVerificationToken $token): void
    {
        $token->update(['last_scanned_at' => now()]);
    }

    public function markVerified(Guard $guard, int $verifiedByUserId): void
    {
        $guard->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verified_by_user_id' => $verifiedByUserId,
        ]);

        if (! $guard->activeVerificationToken()) {
            $this->issueToken($guard);
        }
    }

    public function suspend(Guard $guard): void
    {
        $guard->update(['verification_status' => 'suspended']);

        GuardVerificationToken::query()
            ->where('guard_id', $guard->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function submitForReview(Guard $guard): void
    {
        $guard->update(['verification_status' => 'pending']);
        $this->revokeActiveTokens($guard);
    }

    /**
     * @return array{ready: bool, items: array<int, array{label: string, passed: bool}>}
     */
    public function vettingChecklist(Guard $guard): array
    {
        $hasPhoto = (bool) $guard->photo_path;
        $hasIdDocument = $guard->documents()->whereIn('type', ['id', 'national_id', 'passport'])->exists();
        $licenseValid = $guard->license_number
            && ($guard->license_expires_at === null || $guard->license_expires_at->isFuture());
        $certsCurrent = $guard->certifications()
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()->toDateString());
            })
            ->exists();

        $items = [
            ['label' => 'Photo uploaded', 'passed' => $hasPhoto, 'tab' => 'overview'],
            ['label' => 'ID document on file', 'passed' => $hasIdDocument, 'tab' => 'documents'],
            ['label' => 'License valid', 'passed' => $licenseValid, 'tab' => 'overview'],
            ['label' => 'At least one current certification', 'passed' => $certsCurrent, 'tab' => 'certifications'],
        ];

        return [
            'ready' => collect($items)->every(fn ($item) => $item['passed']),
            'items' => $items,
        ];
    }

    public function currentAssignmentSiteName(Guard $guard): ?string
    {
        if (! $guard->show_current_assignment || $guard->status !== 'active') {
            return null;
        }

        $assignment = $guard->assignments()
            ->whereHas('shift', fn ($q) => $q->where('starts_at', '<=', now())->where('ends_at', '>=', now()))
            ->with('shift.site')
            ->latest('assigned_at')
            ->first();

        return $assignment?->shift?->site?->name;
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = strtoupper(Str::random(10));
        } while (GuardVerificationToken::query()->where('token', $token)->exists());

        return $token;
    }
}
