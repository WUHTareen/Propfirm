<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Services\Payments\Contracts\PaymentGateway;
use Illuminate\Http\Request;

/**
 * No external service. The order is created as pending and the trader is shown
 * the firm's wallet address to send crypto to; an admin confirms receipt from
 * the back office (there is no automated webhook). Good for launch before a
 * gateway account exists.
 */
class ManualGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'manual';
    }

    public function createPayment(Order $order, string $method): PaymentIntent
    {
        $wallet = config("payments.gateways.manual.wallets.{$method}") ?: null;

        return new PaymentIntent(
            reference: $order->order_number,
            payAddress: $wallet,
            payAmount: number_format((float) $order->total, 2, '.', ''),
            payCurrency: 'USD',
            meta: ['manual' => true],
        );
    }

    /**
     * Manual payments are never auto-confirmed via webhook.
     */
    public function parseWebhook(Request $request): ?WebhookResult
    {
        return null;
    }
}
