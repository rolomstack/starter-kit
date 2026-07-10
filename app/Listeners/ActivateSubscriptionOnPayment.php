<?php

namespace App\Listeners;

use App\Actions\Subscriptions\ActivateSubscription;
use App\Actions\Subscriptions\RenewSubscription;
use App\Enums\SubscriptionStatus;
use App\Events\PaymentSucceeded;
use App\Models\Subscription;

class ActivateSubscriptionOnPayment
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected ActivateSubscription $activateSubscription
    ) {}

    /**
     * Handle the event.
     */
    public function handle(PaymentSucceeded $event): void
    {
        $payment = $event->payment;

        if ($payment->payable_type === (new Subscription)->getMorphClass()) {
            $subscription = $payment->payable;

            if ($subscription instanceof Subscription) {
                if ($subscription->status === SubscriptionStatus::Pending || $subscription->status === SubscriptionStatus::PastDue) {
                    $this->activateSubscription->execute($subscription);
                } elseif ($subscription->status === SubscriptionStatus::Active) {
                    app(RenewSubscription::class)->execute($subscription);
                }
            }
        }
    }
}
