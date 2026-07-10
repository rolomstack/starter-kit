<?php

namespace App\Concerns;

use App\Models\PaymentMethod;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSubscriptions
{
    /**
     * @return MorphMany<Subscription, $this>
     */
    public function subscriptions(): MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    /**
     * @return MorphMany<PaymentMethod, $this>
     */
    public function paymentMethods(): MorphMany
    {
        return $this->morphMany(PaymentMethod::class, 'payable');
    }

    /**
     * Get the active subscription for the model.
     */
    public function activeSubscription(): ?Subscription
    {
        return $this->subscriptions()
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('trial_ends_at')
                            ->where('trial_ends_at', '>', now());
                    });
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->latest()
            ->first();
    }

    /**
     * Determine if the model has any active subscription.
     */
    public function isSubscribed(): bool
    {
        return $this->activeSubscription() !== null;
    }

    /**
     * Determine if the model has a given subscription plan.
     */
    public function subscribedTo(string $planSlug): bool
    {
        $subscription = $this->activeSubscription();

        if (! $subscription) {
            return false;
        }

        return $subscription->plan->slug === $planSlug;
    }

    /**
     * Determine if the model is on a trial.
     */
    public function onTrial(): bool
    {
        $subscription = $this->activeSubscription();

        if (! $subscription) {
            return false;
        }

        return $subscription->onTrial();
    }
}
