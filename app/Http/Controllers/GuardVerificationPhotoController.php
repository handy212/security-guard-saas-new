<?php

namespace App\Http\Controllers;

use App\Services\GuardVerificationService;
use App\Services\TenantFileStorageService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuardVerificationPhotoController extends Controller
{
    public function __invoke(
        GuardVerificationService $verification,
        TenantFileStorageService $storage,
        ?string $tenant = null,
        ?string $token = null,
    ): StreamedResponse {
        if ($token === null) {
            $token = $tenant;
            $tenant = null;
        }

        $record = $verification->findValidToken($token, $tenant);

        abort_unless($record, 404);

        $guard = $record->assignedGuard;
        $path = $guard?->photo_path;

        abort_unless($path && $storage->exists($path), 404);

        return $storage->response($path);
    }
}
