<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Orders\OrderFulfillmentService;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function handle(Request $request, string $gateway, PaymentManager $payments, OrderFulfillmentService $fulfillment)
    {
        try {
            $driver = $payments->driver($gateway);
        } catch (\InvalidArgumentException) {
            abort(404);
        }

        $result = $driver->parseWebhook($request);

        // Null = signature failed / not verifiable. Reject without leaking why.
        if (! $result) {
            Log::warning('Payment webhook rejected', ['gateway' => $gateway]);

            return response()->json(['ok' => false], 400);
        }

        $order = Order::where('order_number', $result->reference)
            ->orWhere('payment_reference', $result->reference)
            ->first();

        if (! $order) {
            return response()->json(['ok' => false, 'reason' => 'unknown_order'], 404);
        }

        if ($result->isPaid()) {
            $fulfillment->markPaid($order, $result->txid);
        } elseif ($result->status === 'failed') {
            // Only a still-pending order may transition to failed.
            if ($order->status === 'pending') {
                $order->forceFill(['status' => 'expired'])->save();
            }
        }

        return response()->json(['ok' => true]);
    }
}
