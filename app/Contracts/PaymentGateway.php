<?php

namespace App\Contracts;

use App\DTOs\Payments\GatewayInitialization;
use App\DTOs\Payments\GatewayTransaction;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;

interface PaymentGateway
{
    /**
     * The name of the gateway (e.g. 'paystack', 'stripe')
     */
    public function name(): string;

    /**
     * Create a customer representation in the gateway.
     */
    public function createCustomer(Model $payable): array;

    /**
     * Initialize a transaction on the gateway (e.g. for tokenizing a card).
     */
    public function initializeTransaction(Model $payable, int $amount, string $callbackUrl): GatewayInitialization;

    /**
     * Cancel an existing subscription on the gateway.
     * (We may no longer need this if the app is solely responsible for billing, but kept for compatibility).
     */

    /**
     * Charge a previously tokenized payment method.
     */
    public function chargeToken(PaymentMethod $method, float $amount, string $currency, string $reference): GatewayTransaction;

    /**
     * Verify a transaction and return its details.
     */
    public function verifyTransaction(string $reference): ?GatewayTransaction;

    /**
     * Parse a webhook payload into a GatewayTransaction.
     */
    public function parseWebhookPayload(array $payload): GatewayTransaction;
}
