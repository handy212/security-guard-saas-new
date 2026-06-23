<?php

namespace App\Http\Controllers;

use App\Services\PaystackBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaystackWebhookController extends Controller
{
    public function __invoke(Request $request, PaystackBillingService $paystack): JsonResponse
    {
        abort_unless($paystack->verifyWebhookSignature($request), 403);

        $event = $request->input('event');
        $data = $request->input('data', []);

        $paystack->handleWebhookEvent($event, $data);

        return response()->json(['received' => true]);
    }
}
