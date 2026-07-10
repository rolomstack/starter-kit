<?php

namespace App\Actions\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Model;

class CreateSubscription
{
    public function execute(Model $subscribable, Plan $plan): Subscription
    {
        // Cancel any existing pending subscriptions to avoid clutter
        $subscribable->subscriptions()->where('status', SubscriptionStatus::Pending)->delete();

        // Store the intent in the database
        return $subscribable->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Pending,
        ]);
    }
}
