<?php

namespace App\Services\Checkout;

use App\Models\ChallengePlan;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\RewardPoint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns a checkout selection into a pending Order, applying coupon and points
 * atomically (order + coupon usage + points ledger all commit together).
 */
class CheckoutService
{
    public function __construct(protected PricingService $pricing) {}

    public function placeOrder(
        User $user,
        ChallengePlan $plan,
        string $platform,
        string $method,
        ?string $couponCode = null,
        int $pointsToRedeem = 0,
        bool $cashbackOptIn = false,
    ): Order {
        $coupon = $couponCode
            ? Coupon::whereRaw('LOWER(code) = ?', [Str::lower($couponCode)])->first()
            : null;

        return DB::transaction(function () use ($user, $plan, $platform, $method, $coupon, $pointsToRedeem, $cashbackOptIn) {
            $breakdown = $this->pricing->quote($plan, $coupon, $pointsToRedeem, $user);

            $order = Order::create([
                'order_number' => $this->uniqueOrderNumber(),
                'user_id' => $user->id,
                'challenge_plan_id' => $plan->id,
                'plan_snapshot' => [
                    'name' => $plan->name,
                    'challenge_type' => $plan->challenge_type,
                    'account_size' => (float) $plan->account_size,
                    'price' => (float) $plan->price,
                    'profit_split_percent' => (float) $plan->profit_split_percent,
                    'daily_drawdown_percent' => (float) $plan->daily_drawdown_percent,
                    'max_drawdown_percent' => (float) $plan->max_drawdown_percent,
                    'phases' => $plan->phases,
                ],
                'platform' => $platform,
                'account_size' => $plan->account_size,
                'subtotal' => $breakdown->subtotal,
                'coupon_id' => $breakdown->coupon?->id,
                'discount_amount' => $breakdown->discountAmount,
                'points_redeemed' => $breakdown->pointsRedeemed,
                'points_value' => $breakdown->pointsValue,
                'cashback_opt_in' => $cashbackOptIn,
                'total' => $breakdown->total,
                'currency' => 'USD',
                'status' => 'pending',
                'payment_method' => $method,
                'payment_gateway' => app(\App\Services\Payments\PaymentManager::class)->activeName(),
            ]);

            // Spend redeemed points against the ledger.
            if ($breakdown->pointsRedeemed > 0) {
                $newBalance = (int) $user->points_balance - $breakdown->pointsRedeemed;

                RewardPoint::create([
                    'user_id' => $user->id,
                    'type' => 'spend',
                    'source' => 'purchase_redemption',
                    'points' => -$breakdown->pointsRedeemed,
                    'balance_after' => $newBalance,
                    'description' => "Redeemed on order {$order->order_number}",
                    'order_id' => $order->id,
                ]);

                $user->forceFill(['points_balance' => $newBalance])->save();
            }

            // Record coupon usage.
            if ($breakdown->coupon) {
                CouponUsage::create([
                    'coupon_id' => $breakdown->coupon->id,
                    'user_id' => $user->id,
                    'order_id' => $order->id,
                    'discount_amount' => $breakdown->discountAmount,
                ]);

                $breakdown->coupon->increment('used_count');
            }

            return $order;
        });
    }

    protected function uniqueOrderNumber(): string
    {
        do {
            $number = 'ORD-'.now()->format('Y').'-'.Str::upper(Str::random(6));
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
