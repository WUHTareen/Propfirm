<?php

namespace App\Services\Payments\Contracts;

use App\Models\Order;
use App\Services\Payments\PaymentIntent;
use App\Services\Payments\WebhookResult;
use Illuminate\Http\Request;

interface PaymentGateway
{
    /**
     * The gateway's config key (e.g. "manual", "nowpayments").
     */
    public function key(): string;

    /**
     * Start a payment for the given order and chosen crypto method.
     */
    public function createPayment(Order $order, string $method): PaymentIntent;

    /**
     * Verify and parse an incoming webhook. Returns null when the request
     * fails signature verification (caller should respond 400 and ignore).
     */
    public function parseWebhook(Request $request): ?WebhookResult;
}
