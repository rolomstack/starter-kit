<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\DTOs\Payments\GatewayInitialization;
use App\DTOs\Payments\GatewayTransaction;
use App\Models\Business;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class PaystackGateway implements PaymentGateway
{
    protected string $baseUrl = 'https://api.paystack.co';

    protected string $secretKey;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key') ?? '';
    }

    public function name(): string
    {
        return 'paystack';
    }

    /**
     * @param  Business  $customer
     * @return array<string, mixed>
     */
    public function createCustomer(Model $customer): array
    {
        /** @var Business $customer */
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/customer", [
                'email' => $customer->email,
                'first_name' => $customer->name,
                // Pass business name as last_name for representation if needed
                'last_name' => $customer->is_personal ? '' : 'Business',
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to create customer on Paystack: '.$response->body());
        }

        return $response->json('data');
    }

    public function initializeTransaction(Model $payable, int $amount, string $callbackUrl): GatewayInitialization
    {
        /** @var Business $payable */
        // Try to get a previous customer code. Assuming $payable might have payment methods
        $customerCode = $payable->paymentMethods()->latest()->first()?->gateway_customer_id;

        if (! $customerCode) {
            $customer = $this->createCustomer($payable);
            $customerCode = $customer['customer_code'];
        }

        // Initialize a standard transaction
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'email' => $payable->email,
                'amount' => $amount, // in kobo
                'callback_url' => $callbackUrl,
            ]);

        if ($response->failed()) {
            throw new \Exception('Failed to initialize Paystack transaction: '.$response->body());
        }

        return new GatewayInitialization(
            reference: $response->json('data.reference'),
            checkoutUrl: $response->json('data.authorization_url'),
            customerId: $customerCode,
            raw: $response->json() ?? []
        );
    }

    public function chargeToken(PaymentMethod $paymentMethod, int $amount, string $currency, string $reference): GatewayTransaction
    {
        /** @var Business $payable */
        $payable = $paymentMethod->payable;

        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/charge_authorization", [
                'authorization_code' => $paymentMethod->gateway_token,
                'email' => $payable->email,
                'amount' => $amount,
                'reference' => $reference,
            ]);

        return $this->mapToTransactionDto($response->json() ?? []);
    }

    public function verifyTransaction(string $reference): ?GatewayTransaction
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if ($response->failed() || ! $response->json('status')) {
            return null;
        }

        return $this->mapToTransactionDto($response->json('data') ?? []);
    }

    public function parseWebhookPayload(array $payload): GatewayTransaction
    {
        return $this->mapToTransactionDto($payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function mapToTransactionDto(array $data): GatewayTransaction
    {
        // Note: For chargeToken, the data is sometimes nested in 'data' key depending on Paystack response
        $actualData = isset($data['data']) ? $data['data'] : $data;
        $status = $actualData['status'] ?? ($data['status'] ?? '');

        return new GatewayTransaction(
            isSuccessful: $status === 'success',
            reference: $actualData['reference'] ?? null,
            errorMessage: $actualData['gateway_response'] ?? ($actualData['message'] ?? null),
            authorizationCode: $actualData['authorization']['authorization_code'] ?? null,
            customerId: $actualData['customer']['customer_code'] ?? null,
            cardLast4: $actualData['authorization']['last4'] ?? null,
            cardExpMonth: $actualData['authorization']['exp_month'] ?? null,
            cardExpYear: $actualData['authorization']['exp_year'] ?? null,
            cardBrand: $actualData['authorization']['card_type'] ?? null,
            raw: $data
        );
    }
}
