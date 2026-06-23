<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\StripeBillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, StripeBillingService $stripe)
    {
        $secret = config('stripe.webhook_secret');

        if ($secret && $request->header('Stripe-Signature')) {
            // Production should verify signature with stripe-php; scaffold logs payload for now.
            Log::info('Stripe webhook received', ['type' => $request->input('type')]);
        }

        $type = $request->input('type');
        $object = $request->input('data.object', []);

        if ($type === 'checkout.session.completed') {
            $tenantId = data_get($object, 'metadata.tenant_id');
            $planId = data_get($object, 'metadata.plan_id');
            $subscriptionId = data_get($object, 'subscription');

            if ($tenantId && $subscriptionId) {
                $tenant = Tenant::find($tenantId);
                if ($tenant) {
                    if ($planId) {
                        $tenant->update(['plan_id' => $planId]);
                    }
                    $stripe->syncSubscription($tenant, $subscriptionId);
                }
            }
        }

        return response()->json(['received' => true]);
    }
}
