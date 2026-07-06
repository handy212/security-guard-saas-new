<?php

namespace App\Services;

use App\Models\Guard;
use App\Models\GuardVerificationToken;
use Illuminate\Support\Str;

class GuardVerificationService
{
    public function verificationUrl(GuardVerificationToken $token): string
    {
        $token->loadMissing('assignedGuard.tenant');

        $slug = $token->assignedGuard?->tenant?->slug;

        if ($slug) {
            return url('/g/'.$slug.'/'.$token->token);
        }

        return url('/g/'.$token->token);
    }

    /**
     * Issue a token only when the guard has none active (safe for verify flow).
     */
    public function ensureToken(Guard $guard): GuardVerificationToken
    {
        if ($guard->verification_status !== 'verified') {
            throw new \InvalidArgumentException('Guard must be verified before issuing a QR token.');
        }

        $existing = $guard->activeVerificationToken();
        if ($existing) {
            return $existing;
        }

        return $this->createToken($guard);
    }

    /**
     * Revoke the current token and issue a new one (invalidates printed ID cards).
     */
    public function rotateToken(Guard $guard): GuardVerificationToken
    {
        if ($guard->verification_status !== 'verified') {
            throw new \InvalidArgumentException('Guard must be verified before issuing a QR token.');
        }

        GuardVerificationToken::query()
            ->where('guard_id', $guard->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);

        return $this->createToken($guard);
    }

    /** @deprecated Use ensureToken() or rotateToken() */
    public function issueToken(Guard $guard): GuardVerificationToken
    {
        return $this->rotateToken($guard);
    }

    public function revokeActiveTokens(Guard $guard): void
    {
        GuardVerificationToken::query()
            ->where('guard_id', $guard->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    public function findValidToken(string $token, ?string $tenantSlug = null): ?GuardVerificationToken
    {
        $query = GuardVerificationToken::query()
            ->where('token', $token)
            ->with(['assignedGuard.branch', 'assignedGuard.certifications', 'assignedGuard.skills', 'assignedGuard.tenant']);

        if ($tenantSlug !== null) {
            $query->whereHas('assignedGuard.tenant', fn ($q) => $q->where('slug', $tenantSlug));
        }

        $record = $query->first();

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
            $this->ensureToken($guard);
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
        $hasPoliceClearance = $guard->documents()->where('type', 'police_clearance')->exists();
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
            ['label' => 'Police clearance on file', 'passed' => $hasPoliceClearance, 'tab' => 'documents'],
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

    /**
     * @return array{can_download: bool, message: ?string, action: ?string}
     */
    public function idCardEligibility(Guard $guard): array
    {
        if ($guard->verification_status === 'suspended') {
            return [
                'can_download' => false,
                'message' => 'Verification is suspended. Reinstate this guard before issuing an ID card.',
                'action' => null,
            ];
        }

        if ($guard->verification_status !== 'verified') {
            return [
                'can_download' => false,
                'message' => 'Complete the verification checklist and mark this guard as verified.',
                'action' => 'verification',
            ];
        }

        if (! $guard->activeVerificationToken()) {
            return [
                'can_download' => false,
                'message' => 'An active QR verification token is required. Rotate the QR code below if needed.',
                'action' => 'regenerate',
            ];
        }

        return [
            'can_download' => true,
            'message' => null,
            'action' => null,
        ];
    }

    private function generateUniqueToken(): string
    {
        do {
            $token = strtoupper(Str::random(10));
        } while (GuardVerificationToken::query()->where('token', $token)->exists());

        return $token;
    }

    private function createToken(Guard $guard): GuardVerificationToken
    {
        $ttlDays = $this->tokenTtlDays($guard);

        return GuardVerificationToken::create([
            'tenant_id' => $guard->tenant_id,
            'guard_id' => $guard->id,
            'token' => $this->generateUniqueToken(),
            'expires_at' => $ttlDays > 0 ? now()->addDays($ttlDays) : null,
        ]);
    }

    private function tokenTtlDays(Guard $guard): int
    {
        $guard->loadMissing('tenant');

        $settings = \App\Models\TenantSetting::query()
            ->where('tenant_id', $guard->tenant_id)
            ->where('key', 'verification')
            ->value('value') ?? [];

        $ttl = $settings['token_ttl_days'] ?? config('guard_verification.token_ttl_days', 365);

        return max(0, (int) $ttl);
    }
}
