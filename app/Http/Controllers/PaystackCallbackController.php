<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\PaystackBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaystackCallbackController extends Controller
{
    public function __invoke(Request $request, PaystackBillingService $paystack): RedirectResponse
    {
        $reference = $request->query('reference');

        if (! $reference) {
            return redirect()->route('billing.subscription')->with('status', 'Payment reference missing.');
        }

        $data = $paystack->verifyTransaction($reference);

        if (! $data) {
            return redirect()->route('billing.subscription')->with('status', 'Could not verify payment. Contact support if you were charged.');
        }

        $tenantId = data_get($data, 'metadata.tenant_id');
        $planId = data_get($data, 'metadata.plan_id');
        $tenant = Tenant::find($tenantId);
        $plan = SubscriptionPlan::find($planId);

        if ($tenant && $plan) {
            $paystack->activateFromPayment($tenant, $plan, $data);

            return redirect()->route('billing.subscription')->with('status', 'Payment successful! Your '.$plan->name.' plan is now active.');
        }

        return redirect()->route('billing.subscription')->with('status', 'Payment verified but tenant metadata was missing.');
    }
}
