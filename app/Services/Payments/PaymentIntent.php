<?php

namespace App\Services\Payments;

/**
 * The result of asking a gateway to start a payment for an order.
 *
 * A hosted-checkout gateway returns a `checkoutUrl` to redirect to. A manual
 * flow returns a `payAddress` (wallet) the trader sends crypto to instead.
 */
class PaymentIntent
{
    public function __construct(
        public readonly string $reference,     // gateway reference / our order number
        public readonly ?string $checkoutUrl = null,
        public readonly ?string $payAddress = null,
        public readonly ?string $payAmount = null,
        public readonly ?string $payCurrency = null,
        public readonly array $meta = [],
    ) {}
}
