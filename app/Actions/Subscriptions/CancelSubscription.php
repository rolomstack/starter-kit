<?php

namespace App\Actions\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;

class CancelSubscription
{
    public function execute(Subscription $subscription): bool
    {
        return $subscription->update([
            'status' => SubscriptionStatus::Canceled,
            'ends_at' => now(), // Or end of billing cycle depending on your business rules
        ]);
    }
}
