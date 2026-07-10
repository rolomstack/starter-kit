<?php

namespace App\Contracts;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Subscribable
{
    /**
     * @return MorphMany<Subscription, covariant \Illuminate\Database\Eloquent\Model>
     */
    public function subscriptions(): MorphMany;
}
