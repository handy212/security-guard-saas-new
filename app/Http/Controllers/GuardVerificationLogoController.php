<?php

namespace App\Http\Controllers;

use App\Models\TenantSetting;
use App\Services\GuardVerificationService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuardVerificationLogoController extends Controller
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
        abort_unless($guard, 404);

        $path = TenantSetting::query()
            ->where('tenant_id', $guard->tenant_id)
            ->where('key', 'id_card')
            ->value('value')['logo_path'] ?? null;

        abort_unless($path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }
}
