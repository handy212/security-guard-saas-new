<?php

namespace App\Http\Controllers;

use App\Models\Guard;
use App\Services\GuardIdCardRenderService;
use App\Services\QrCodeService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class GuardIdCardPreviewController extends Controller
{
    public function guard(Guard $guard, GuardIdCardRenderService $renderer, QrCodeService $qr): View|Response
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        abort_unless((int) $guard->tenant_id === (int) auth()->user()->tenant_id, 404);

        if ($guard->verification_status !== 'verified' || ! $guard->activeVerificationToken()) {
            return view('id-cards.preview-unavailable', [
                'message' => 'Verify this guard and activate a QR token to preview the ID card.',
            ]);
        }

        $built = $renderer->forGuard($guard);
        $viewData = $built['viewData'];
        $renderer->cleanup($built['tempFiles']);

        $side = in_array(request()->query('side'), ['front', 'back'], true)
            ? request()->query('side')
            : 'front';

        return view('id-cards.embedded-preview', [
            'brand' => $viewData['brand'],
            'card' => $viewData['card'],
            'side' => $side,
            'photoUrl' => $viewData['photoUrl'],
            'logoUrl' => $viewData['logoUrl'],
            'qrSvg' => $qr->svg($viewData['verifyUrl'], 56),
        ]);
    }
}
