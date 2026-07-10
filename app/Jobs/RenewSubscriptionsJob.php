<?php

namespace App\Jobs;

use App\Actions\Payments\ChargePayment;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RenewSubscriptionsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ChargePayment $charger): void
    {
        // Find subscriptions that are active but expired
        $subscriptions = Subscription::with(['plan', 'payments' => function ($query) {
            $query->where('status', PaymentStatus::Successful)->latest();
        }, 'payments.paymentMethod'])
            ->where('status', SubscriptionStatus::Active)
            ->where('ends_at', '<=', now())
            ->get();

        foreach ($subscriptions as $subscription) {
            $paymentMethod = $subscription->payments->first()?->paymentMethod;

            if (! $paymentMethod) {
                $subscription->update(['status' => SubscriptionStatus::PastDue]);

                continue;
            }

            // Create a pending payment
            /** @var Payment $payment */
            $payment = $subscription->payments()->create([
                'amount' => $subscription->plan->price,
                'status' => PaymentStatus::Pending,
                'gateway_name' => $paymentMethod->gateway_name ?? 'paystack',
            ]);

            // Attempt to charge the payment
            $success = $charger->execute($payment, $paymentMethod);

            if (! $success) {
                Log::warning("Failed to renew subscription {$subscription->id}");
                $subscription->update(['status' => SubscriptionStatus::PastDue]);
            }
        }
    }
}
