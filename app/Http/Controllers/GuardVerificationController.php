<?php

namespace App\Http\Controllers;

use App\Services\GuardVerificationPagePresenter;
use App\Services\GuardVerificationService;
use App\Services\TenantFileStorageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuardVerificationController extends Controller
{
    public function __invoke(
        Request $request,
        GuardVerificationService $verification,
        GuardVerificationPagePresenter $pagePresenter,
        TenantFileStorageService $storage,
        ?string $tenant = null,
        ?string $token = null,
    ): View {
        if ($token === null) {
            $token = $tenant;
            $tenant = null;
        }

        $record = $verification->findValidToken($token, $tenant);

        abort_unless($record, 404);

        $guard = $record->assignedGuard;

        abort_unless($guard, 404);

        $verification->recordScan($record);

        $guard->loadMissing('tenant');

        $isSuspended = $guard->verification_status === 'suspended';
        $currentAssignment = $isSuspended ? null : $verification->currentAssignment($guard);

        return view('verify.guard', $pagePresenter->present(
            $guard,
            $token,
            $currentAssignment,
            $guard->skills,
            $guard->verification_status === 'verified',
            $guard->verified_at,
            now(),
            $this->verificationPhotoUrl($guard, $token, $storage),
        ) + [
            'isSuspended' => $isSuspended,
            'suspendedMessage' => $isSuspended ? $verification->dutyTypeSuspendedMessage($guard) : null,
        ]);
    }

    private function verificationPhotoUrl($guard, string $token, TenantFileStorageService $storage): ?string
    {
        if (! $guard->photo_path || ! $storage->exists($guard->photo_path)) {
            return null;
        }

        $slug = $guard->tenant?->slug;

        if ($slug) {
            return route('guard.verify.photo', ['tenant' => $slug, 'token' => $token]);
        }

        return route('guard.verify.photo.legacy', $token);
    }
}
