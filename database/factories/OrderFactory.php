<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = 99;

        return [
            'order_number' => 'ORD-'.now()->format('Y').'-'.Str::upper(Str::random(6)),
            'user_id' => User::factory(),
            'plan_snapshot' => ['name' => '2-Step $10,000', 'challenge_type' => 'two_step', 'account_size' => 10000],
            'platform' => 'mt5',
            'account_size' => 10000,
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'points_redeemed' => 0,
            'points_value' => 0,
            'cashback_opt_in' => false,
            'total' => $subtotal,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_method' => 'usdt_bsc',
            'payment_gateway' => 'manual',
        ];
    }
}
