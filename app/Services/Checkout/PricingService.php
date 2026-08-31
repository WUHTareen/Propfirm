<?php

namespace App\Services\Checkout;

use App\Models\ChallengePlan;
use App\Models\Coupon;
use App\Models\User;

/**
 * Computes a checkout total from a plan, an optional coupon, and redeemed
 * cashback points. Pure calculation — it never writes to the database.
 */
class PricingService
{
    /**
     * @param  int  $pointsToRedeem  raw points the trader wants to spend
     */
    public function quote(ChallengePlan $plan, ?Coupon $coupon, int $pointsToRedeem, User $user): PriceBreakdown
    {
        $subtotal = round((float) $plan->price, 2);

        // --- Coupon ---------------------------------------------------------
        $discount = 0.0;
        $appliedCoupon = null;

        if ($coupon && $this->couponApplies($coupon, $user, $subtotal)) {
            $discount = $coupon->type === 'percent'
                ? round($subtotal * ((float) $coupon->value / 100), 2)
                : round((float) $coupon->value, 2);

            $discount = min($discount, $subtotal); // never below zero
            $appliedCoupon = $coupon;
        }

        $afterDiscount = round($subtotal - $discount, 2);

        // --- Points (100 points = $1.00) ------------------------------------
        $pointsPerDollar = (int) config('payments.points_per_dollar', 100);
        $pointsRedeemed = max(0, $pointsToRedeem);

        // Cap to the user's balance and to what's still owed.
        $pointsRedeemed = min($pointsRedeemed, (int) $user->points_balance);
        $maxUsablePoints = (int) round($afterDiscount * $pointsPerDollar);
        $pointsRedeemed = min($pointsRedeemed, $maxUsablePoints);

        $pointsValue = round($pointsRedeemed / $pointsPerDollar, 2);

        $total = round(max(0, $afterDiscount - $pointsValue), 2);

        return new PriceBreakdown(
            subtotal: $subtotal,
            discountAmount: $discount,
            coupon: $appliedCoupon,
            pointsRedeemed: $pointsRedeemed,
            pointsValue: $pointsValue,
            total: $total,
        );
    }

    /**
     * Whether a coupon may be applied for this user and order amount.
     */
    public function couponApplies(Coupon $coupon, User $user, float $subtotal): bool
    {
        if (! $coupon->isCurrentlyValid()) {
            return false;
        }

        if ($coupon->min_order_amount !== null && $subtotal < (float) $coupon->min_order_amount) {
            return false;
        }

        if ($coupon->per_user_limit !== null) {
            $used = $coupon->usages()->where('user_id', $user->id)->count();
            if ($used >= $coupon->per_user_limit) {
                return false;
            }
        }

        return true;
    }
}
