<?php

namespace App\Contracts;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Billable
{
    /**
     * @return MorphMany<PaymentMethod, covariant \Illuminate\Database\Eloquent\Model>
     */
    public function paymentMethods(): MorphMany;
}
