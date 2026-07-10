<?php

namespace App\Actions\Payments;

use App\Contracts\Payable;
use App\Contracts\PaymentGateway;
use App\DTOs\Payments\GatewayTransaction;
use App\Enums\PaymentStatus;
use App\Events\PaymentSucceeded;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ConfirmPayment
{
    public function __construct(
        protected PaymentGateway $gateway,
        protected UpdatePaymentMethod $updatePaymentMethod
    ) {}

    public function execute(string $reference, ?GatewayTransaction $transaction = null): bool
    {
        return DB::transaction(function () use ($reference, $transaction) {
            // Pessimistic lock on the payment record
            $payment = Payment::where('gateway_reference', $reference)->lockForUpdate()->first();

            if (! $payment) {
                return false;
            }

            // If already successful, just return true
            if ($payment->status->successful()) {
                return true;
            }

            // If transaction data isn't provided (e.g. called from callback), fetch it from gateway
            if (! $transaction) {
                try {
                    $transaction = $this->gateway->verifyTransaction($reference);
                } catch (\Exception $e) {
                    Log::error('Failed to verify payment transaction: '.$e->getMessage());

                    return false;
                }
            }

            if ($transaction && $transaction->isSuccessful) {
                $paymentMethodId = null;

                // Create or update the PaymentMethod based on tokenization data
                if ($payment->payable instanceof Payable) {
                    $owner = $payment->payable->getPayer();
                    $paymentMethod = $this->updatePaymentMethod->execute($owner, $transaction);
                    $paymentMethodId = $paymentMethod?->id;
                }

                $payment->update([
                    'status' => PaymentStatus::Successful,
                    'payment_method_id' => $paymentMethodId,
                ]);

                // Broadcast generic event
                PaymentSucceeded::dispatch($payment, $transaction->raw);

                return true;
            }

            $payment->update([
                'status' => PaymentStatus::Failed,
                'error_message' => $transaction ? $transaction->errorMessage : 'Payment failed',
            ]);

            return false;
        });
    }
}
