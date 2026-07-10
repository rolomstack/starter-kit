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
     * Initialize a transaction on the gateway (e.g. for tokenizing a card).
     */
    public function initializeTransaction(Model $payable, int $amount, string $callbackUrl): GatewayInitialization;

    /**
     * Create a customer profile on the gateway (optional).
     *
     * @return array<string, mixed>|null Gateway response payload or null if not supported
     */
    public function createCustomer(Model $customer): ?array;

    /**
     * Charge an existing gateway token/authorization.
     * Amount should be in lowest denomination (e.g., kobo for NGN).
     */
    public function chargeToken(PaymentMethod $paymentMethod, int $amount, string $currency, string $reference): ?GatewayTransaction;

    /**
     * Verify a transaction and return its details.
     */
    public function verifyTransaction(string $reference): ?GatewayTransaction;

    /**
     * Parse a webhook payload into a GatewayTransaction.
     *
     * @param  array<string, mixed>  $payload
     */
    public function parseWebhookPayload(array $payload): GatewayTransaction;
}
