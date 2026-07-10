<?php

namespace App\Actions\Payments;

use App\Contracts\PaymentGateway;
use App\DTOs\Payments\GatewayTransaction;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

class UpdatePaymentMethod
{
    public function __construct(
        protected PaymentGateway $gateway
    ) {}

    /**
     * @param  Model  $owner  Owner of the payment method (e.g. Business or User)
     * @param  GatewayTransaction  $transaction  The gateway transaction payload
     */
    public function execute(Model $owner, GatewayTransaction $transaction): ?PaymentMethod
    {
        if ($transaction->authorizationCode) {
            $paymentMethod = $owner->paymentMethods()->updateOrCreate(
                ['gateway_token' => $transaction->authorizationCode],
                [
                    'gateway_name' => $this->gateway->name(),
                    'gateway_customer_id' => $transaction->customerId,
                    'last4' => $transaction->cardLast4,
                    'exp_month' => $transaction->cardExpMonth,
                    'exp_year' => $transaction->cardExpYear,
                    'brand' => $transaction->cardBrand,
                    'is_default' => true,
                ]
            );

            // Ensure other payment methods are not default
            $owner->paymentMethods()
                ->where('id', '!=', $paymentMethod->id)
                ->update(['is_default' => false]);

            return $paymentMethod;
        }

        return null;
    }
}
