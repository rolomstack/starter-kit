<?php

namespace App\DTOs\Payments;

class GatewayInitialization
{
    public function __construct(
        public readonly ?string $reference,
        public readonly ?string $checkoutUrl,
        public readonly ?string $customerId,
        public readonly array $raw
    ) {}
}
