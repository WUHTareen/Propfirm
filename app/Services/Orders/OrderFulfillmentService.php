<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\RewardPoint;
use App\Models\TradingAccount;
use Illuminate\Support\Facades\DB;

/**
 * Fulfils a paid order: marks it paid, awards cashback points (if opted in),
 * and provisions a trading account in "pending_assignment" state with the
 * plan's rule thresholds resolved. Admin issues MT5/MT4 credentials later
 * (Phase 05). Idempotent — safe to call more than once for the same order.
 */
class OrderFulfillmentService
{
    public function markPaid(Order $order, ?string $txid = null): Order
    {
        // Already fulfilled — do nothing (webhooks can arrive more than once).
        if (in_array($order->status, ['paid', 'processing', 'completed'], true)) {
            return $order;
        }

        return DB::transaction(function () use ($order, $txid) {
            $order->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'payment_txid' => $txid ?: $order->payment_txid,
            ])->save();

            $this->awardCashback($order);
            $this->provisionAccount($order);

            return $order->refresh();
        });
    }

    protected function awardCashback(Order $order): void
    {
        if (! $order->cashback_opt_in) {
            return;
        }

        $rate = (int) config('payments.cashback_points_per_dollar', 10);
        $points = (int) floor((float) $order->total * $rate);

        if ($points <= 0) {
            return;
        }

        $user = $order->user;
        $newBalance = (int) $user->points_balance + $points;

        RewardPoint::create([
            'user_id' => $user->id,
            'type' => 'earn',
            'source' => 'cashback',
            'points' => $points,
            'balance_after' => $newBalance,
            'description' => "Cashback on order {$order->order_number}",
            'order_id' => $order->id,
        ]);

        $user->forceFill(['points_balance' => $newBalance])->save();
    }

    protected function provisionAccount(Order $order): void
    {
        $snapshot = $order->plan_snapshot ?? [];
        $size = (float) ($snapshot['account_size'] ?? $order->account_size);

        $phase1Target = collect($snapshot['phases'] ?? [])
            ->firstWhere('phase', 1)['profit_target_percent'] ?? null;

        TradingAccount::create([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'challenge_plan_id' => $order->challenge_plan_id,
            'platform' => $order->platform,
            'account_size' => $size,
            'challenge_type' => $snapshot['challenge_type'] ?? 'two_step',
            'current_phase' => 1,
            'status' => 'pending_assignment',
            'starting_balance' => $size,
            'profit_target_amount' => $phase1Target ? round($size * $phase1Target / 100, 2) : null,
            'daily_drawdown_limit' => isset($snapshot['daily_drawdown_percent'])
                ? round($size * $snapshot['daily_drawdown_percent'] / 100, 2) : null,
            'max_drawdown_limit' => isset($snapshot['max_drawdown_percent'])
                ? round($size * $snapshot['max_drawdown_percent'] / 100, 2) : null,
            'profit_split_percent' => $snapshot['profit_split_percent'] ?? null,
        ]);
    }
}
