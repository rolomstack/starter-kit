<?php

namespace App\Http\Controllers\Businesses;

use App\Actions\Payments\ConfirmPayment;
use App\Actions\Payments\InitializePayment;
use App\Actions\Subscriptions\CancelSubscription;
use App\Actions\Subscriptions\CreateSubscription;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutSubscriptionRequest;
use App\Models\Business;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function index(Request $request, Business $business): Response
    {
        Gate::authorize('update', $business);

        $plans = Plan::with('features')->where('is_active', true)->get();
        $activeSubscription = $business->activeSubscription()?->load('plan');

        return Inertia::render('businesses/subscriptions/index', [
            'business' => $business,
            'plans' => $plans,
            'activeSubscription' => $activeSubscription,
        ]);
    }

    public function checkout(
        CheckoutSubscriptionRequest $request,
        Business $business,
        CreateSubscription $creator,
        InitializePayment $initializer
    ): \Symfony\Component\HttpFoundation\Response {
        // Validation and authorization are handled by CheckoutSubscriptionRequest
        /** @var Plan $plan */
        $plan = Plan::findOrFail($request->validated('plan_id'));

        try {
            $subscription = $creator->execute($business, $plan);
            $callbackUrl = route('businesses.subscriptions.callback', $business);

            $payment = $initializer->execute($subscription, $business, $plan->price, $callbackUrl);

            return Inertia::location($payment->metadata['checkout_url']);
        } catch (\Exception $e) {
            return back()->with('error', 'Unable to process subscription at this time. '.$e->getMessage());
        }
    }

    public function callback(Request $request, Business $business, ConfirmPayment $confirmer): RedirectResponse
    {
        Gate::authorize('update', $business);

        $reference = $request->query('reference') ?? $request->query('trxref');

        if (! $reference) {
            return redirect()->route('businesses.subscriptions.index', $business)->with('error', 'Invalid payment reference.');
        }

        try {
            $paymentSuccessful = $confirmer->execute($reference);

            if ($paymentSuccessful) {
                return redirect()->route('businesses.subscriptions.index', $business)->with('success', 'Subscription activated successfully!');
            }

            return redirect()->route('businesses.subscriptions.index', $business)->with('error', 'Payment verification failed or is still pending.');
        } catch (\Exception $e) {
            return redirect()->route('businesses.subscriptions.index', $business)->with('error', 'Error verifying payment.');
        }
    }

    public function cancel(Request $request, Business $business, CancelSubscription $canceler): RedirectResponse
    {
        Gate::authorize('update', $business);

        $subscription = $business->activeSubscription();

        if (! $subscription) {
            return back()->with('error', 'No active subscription found.');
        }

        try {
            $canceler->execute($subscription);

            return back()->with('success', 'Subscription canceled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to cancel subscription.');
        }
    }
}
