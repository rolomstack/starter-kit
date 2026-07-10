<?php

namespace App\Actions\Subscriptions;

use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

class RenewSubscription
{
    public function execute(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            // Lock the subscription to prevent race conditions during activation
            $subscription = Subscription::lockForUpdate()->find($subscription->id);

            // Extend from the current ends_at if it exists, otherwise from now
            $endsAt = $subscription->plan->calculateNextBillingDate($subscription->ends_at);

            $subscription->update([
                'ends_at' => $endsAt,
            ]);

            return $subscription;
        });
    }
}
