<?php

namespace App\Actions\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class ActivateSubscription
{
    public function execute(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            // Lock the subscription to prevent race conditions during activation
            $subscription = Subscription::lockForUpdate()->find($subscription->id);

            $endsAt = $subscription->plan->calculateNextBillingDate();

            $subscription->update([
                'status' => SubscriptionStatus::Active,
                'ends_at' => $endsAt,
            ]);

            return $subscription;
        });
    }
}
