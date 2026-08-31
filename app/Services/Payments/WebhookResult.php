<?php

namespace App\Services\Payments;

/**
 * The normalised outcome parsed from a gateway webhook (IPN).
 * `status` is one of: paid, pending, failed.
 */
class WebhookResult
{
    public function __construct(
        public readonly string $reference, // our order number
        public readonly string $status,
        public readonly ?string $txid = null,
        public readonly array $raw = [],
    ) {}

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
