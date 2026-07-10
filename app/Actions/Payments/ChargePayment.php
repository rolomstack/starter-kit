<?php

namespace App\Actions\Payments;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Events\PaymentSucceeded;
use App\Models\Payment;
use App\Models\PaymentMethod;

class ChargePayment
{
    public function __construct(
        protected PaymentGateway $gateway
    ) {}

    public function execute(Payment $payment, PaymentMethod $paymentMethod): bool
    {
        try {
            $reference = uniqid('rnw_');
            $currency = 'NGN'; // Alternatively, get it from the payment or plan

            $transaction = $this->gateway->chargeToken($paymentMethod, $payment->amount, $currency, $reference);

            if ($transaction->isSuccessful) {
                $payment->update([
                    'status' => PaymentStatus::Successful,
                    'gateway_reference' => $reference,
                    'payment_method_id' => $paymentMethod->id,
                ]);

                PaymentSucceeded::dispatch($payment, $transaction->raw);

                return true;
            }

            $payment->update([
                'status' => PaymentStatus::Failed,
                'gateway_reference' => $reference,
                'error_message' => $transaction->errorMessage ?? 'Gateway charge failed',
            ]);

            return false;
        } catch (\Exception $e) {
            $payment->update([
                'status' => PaymentStatus::Failed,
                'error_message' => substr($e->getMessage(), 0, 255),
            ]);

            return false;
        }
    }
}
