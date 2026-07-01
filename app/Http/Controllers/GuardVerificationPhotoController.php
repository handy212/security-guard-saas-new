<?php

namespace App\Http\Controllers;

use App\Services\GuardVerificationService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuardVerificationPhotoController extends Controller
{
    public function __invoke(string $token, GuardVerificationService $verification): StreamedResponse
    {
        $record = $verification->findValidToken($token);

        abort_unless($record, 404);

        $guard = $record->assignedGuard;
        $path = $guard?->photo_path;

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }
}
