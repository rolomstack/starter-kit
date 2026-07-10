<?php

namespace App\Actions\Payments;

use App\Contracts\Payable;
use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;

class InitializePayment
{
    public function __construct(
        protected PaymentGateway $gateway
    ) {}

    /**
     * @param  Payable  $payable  The entity the payment belongs to (e.g. Subscription, Order)
     * @param  Model  $payer  The entity making the payment (e.g. Business, User)
     * @param  int  $amount  Amount in the lowest currency denominator (e.g. kobo)
     * @param  string  $callbackUrl  URL to redirect to after payment
     */
    public function execute(Payable $payable, Model $payer, int $amount, string $callbackUrl): Payment
    {
        // Cancel any pending payments for this payable to avoid duplicates
        $payable->payments()->where('status', PaymentStatus::Pending)->delete();

        // Initialize transaction on the gateway
        $initialization = $this->gateway->initializeTransaction($payer, $amount, $callbackUrl);

        // Store the intent in the database
        /** @var Payment $payment */
        $payment = $payable->payments()->create([
            'amount' => $amount,
            'status' => PaymentStatus::Pending,
            'gateway_name' => $this->gateway->name(),
            'gateway_reference' => $initialization->reference,
            'metadata' => [
                'checkout_url' => $initialization->checkoutUrl,
                'gateway_response' => $initialization->raw,
            ],
        ]);

        return $payment;
    }
}
