<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Payments\PaymentManager;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()
            ->latest()
            ->paginate(15);

        return view('dashboard.orders', ['orders' => $orders]);
    }

    public function pay(Request $request, Order $order, PaymentManager $payments)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        // Already settled — nothing to pay.
        if ($order->isPaid()) {
            return redirect()->route('dashboard.orders')
                ->with('status', "Order {$order->order_number} is already paid.");
        }

        // Build the payment intent. If the stored gateway is misconfigured
        // (e.g. NOWPayments selected but no API key yet), fall back to manual
        // instructions so checkout never hard-fails.
        try {
            $gateway = $payments->driver($order->payment_gateway);
            $intent = $gateway->createPayment($order, $order->payment_method);
        } catch (\Throwable $e) {
            report($e);
            $intent = app(\App\Services\Payments\ManualGateway::class)
                ->createPayment($order, $order->payment_method);
        }

        // Persist the gateway reference for later reconciliation.
        if ($intent->reference && $intent->reference !== $order->payment_reference) {
            $order->forceFill(['payment_reference' => $intent->reference])->save();
        }

        // Hosted checkout → send the trader straight to the gateway.
        if ($intent->checkoutUrl) {
            return redirect()->away($intent->checkoutUrl);
        }

        return view('dashboard.pay', [
            'order' => $order,
            'intent' => $intent,
        ]);
    }
}
