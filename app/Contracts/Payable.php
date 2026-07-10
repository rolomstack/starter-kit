<?php

namespace App\Contracts;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface Payable
{
    /**
     * Get the entity responsible for paying (e.g., Business, User).
     *
     * @return Billable&Model
     */
    public function getPayer(): Model;

    /**
     * Get the payments for this payable entity.
     *
     * @return MorphMany<Payment, covariant \Illuminate\Database\Eloquent\Model>
     */
    public function payments(): MorphMany;
}
