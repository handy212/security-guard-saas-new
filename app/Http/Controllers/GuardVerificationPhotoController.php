<?php

namespace App\Http\Controllers;

use App\Services\GuardVerificationService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuardVerificationPhotoController extends Controller
{
    public function __invoke(GuardVerificationService $verification, ?string $tenant = null, ?string $token = null): StreamedResponse
    {
        if ($token === null) {
            $token = $tenant;
            $tenant = null;
        }

        $record = $verification->findValidToken($token, $tenant);

        abort_unless($record, 404);

        $guard = $record->assignedGuard;
        $path = $guard?->photo_path;

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }
}
