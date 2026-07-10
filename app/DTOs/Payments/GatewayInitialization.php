<?php

namespace App\DTOs\Payments;

class GatewayInitialization
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $reference,
        public string $checkoutUrl,
        public string $customerId,
        public array $raw
    ) {}
}
