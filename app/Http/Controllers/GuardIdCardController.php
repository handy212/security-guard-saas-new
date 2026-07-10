<?php

namespace App\Http\Controllers;

use App\Models\Guard;
use App\Services\GuardIdCardPdfService;
use App\Services\GuardIdCardRenderService;
use Illuminate\Http\Response;

class GuardIdCardController extends Controller
{
    public function __invoke(
        Guard $guard,
        GuardIdCardRenderService $renderer,
        GuardIdCardPdfService $pdf,
    ): Response {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        abort_unless((int) $guard->tenant_id === (int) auth()->user()->tenant_id, 404);
        abort_unless(in_array($guard->verification_status, ['verified', 'suspended'], true), 403, 'Guard must be verified before downloading an ID card.');

        $built = $renderer->forGuard($guard);

        try {
            $filename = 'guard-id-'.preg_replace('/[^a-zA-Z0-9._-]+/', '-', $guard->employee_number ?: (string) $guard->id).'.pdf';

            return $pdf->downloadResponse($built['viewData'], $filename);
        } finally {
            $renderer->cleanup($built['tempFiles']);
        }
    }
}
