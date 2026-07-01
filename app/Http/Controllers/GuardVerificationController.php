<?php

namespace App\Http\Controllers;

use App\Services\GuardVerificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuardVerificationController extends Controller
{
    public function __invoke(string $token, Request $request, GuardVerificationService $verification): View
    {
        $record = $verification->findValidToken($token);

        abort_unless($record, 404);

        $guard = $record->assignedGuard;

        abort_unless($guard, 404);

        if (in_array($guard->verification_status, ['suspended', 'expired'], true)) {
            abort(404);
        }

        $verification->recordScan($record);

        $currentSite = $verification->currentAssignmentSiteName($guard);

        $certifications = $guard->certifications()
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()->toDateString());
            })
            ->get();

        return view('verify.guard', [
            'token' => $token,
            'guard' => $guard,
            'companyName' => $guard->tenant?->name ?? config('app.name'),
            'branchName' => $guard->branch?->name,
            'currentSite' => $currentSite,
            'certifications' => $certifications,
            'skills' => $guard->skills,
            'isVerified' => $guard->verification_status === 'verified',
            'verifiedAt' => $guard->verified_at,
            'scannedAt' => now(),
        ]);
    }
}
