<?php

namespace App\DTOs\Payments;

class GatewayTransaction
{
    public function __construct(
        public readonly bool $isSuccessful,
        public readonly ?string $reference,
        public readonly ?string $errorMessage,
        public readonly ?string $authorizationCode,
        public readonly ?string $customerId,
        public readonly ?string $cardLast4,
        public readonly ?string $cardExpMonth,
        public readonly ?string $cardExpYear,
        public readonly ?string $cardBrand,
        public readonly array $raw
    ) {}
}
