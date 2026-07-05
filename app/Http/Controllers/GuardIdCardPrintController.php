<?php

namespace App\Http\Controllers;

use App\Models\Guard;
use App\Services\GuardIdCardRenderService;
use App\Services\QrCodeService;
use Illuminate\View\View;

class GuardIdCardPrintController extends Controller
{
    public function __invoke(Guard $guard, GuardIdCardRenderService $renderer, QrCodeService $qr): View
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        abort_unless((int) $guard->tenant_id === (int) auth()->user()->tenant_id, 404);
        abort_unless($guard->verification_status === 'verified', 403, 'Guard must be verified before printing an ID card.');
        abort_unless($guard->activeVerificationToken(), 403, 'An active QR token is required.');

        $built = $renderer->forGuard($guard);

        try {
            $viewData = $built['viewData'];
            $viewData['qrSvg'] = $qr->svg($viewData['verifyUrl'], 56);

            return view('id-cards.print', $viewData);
        } finally {
            $renderer->cleanup($built['tempFiles']);
        }
    }
}
