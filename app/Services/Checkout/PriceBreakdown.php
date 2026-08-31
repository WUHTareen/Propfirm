<?php

namespace App\Services\Checkout;

use App\Models\Coupon;

/**
 * Immutable result of pricing a checkout: what the trader pays and why.
 */
class PriceBreakdown
{
    public function __construct(
        public readonly float $subtotal,
        public readonly float $discountAmount,
        public readonly ?Coupon $coupon,
        public readonly int $pointsRedeemed,
        public readonly float $pointsValue,
        public readonly float $total,
    ) {}
}
