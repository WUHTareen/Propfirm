<?php

namespace App\Services\Payments;

use App\Models\Order;
use App\Models\Setting;
use App\Services\Payments\Contracts\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * NOWPayments hosted-invoice integration. Creates an invoice and redirects the
 * trader to NOWPayments' checkout; confirmation arrives via an IPN webhook
 * whose HMAC-SHA512 signature is verified against the IPN secret.
 *
 * Requires NOWPAYMENTS_API_KEY and NOWPAYMENTS_IPN_SECRET.
 */
class NowPaymentsGateway implements PaymentGateway
{
    public function key(): string
    {
        return 'nowpayments';
    }

    /**
     * Resolve a secret from encrypted admin settings first, then .env/config.
     */
    public static function secret(string $settingKey, string $configKey): ?string
    {
        $stored = Setting::get($settingKey);

        if ($stored) {
            try {
                return Crypt::decryptString($stored);
            } catch (\Throwable) {
                return null;
            }
        }

        return config($configKey);
    }

    public function createPayment(Order $order, string $method): PaymentIntent
    {
        $config = config('payments.gateways.nowpayments');
        $apiKey = static::secret('nowpayments_api_key', 'payments.gateways.nowpayments.api_key');

        if (empty($apiKey)) {
            throw new RuntimeException('NOWPayments API key is not configured.');
        }

        $payload = [
            'price_amount' => (float) $order->total,
            'price_currency' => 'usd',
            'order_id' => $order->order_number,
            'order_description' => 'Challenge purchase '.$order->order_number,
            'ipn_callback_url' => route('webhooks.payment', ['gateway' => 'nowpayments']),
            'success_url' => route('dashboard.orders'),
            'cancel_url' => route('dashboard.orders'),
        ];

        if ($payCurrency = $config['pay_currencies'][$method] ?? null) {
            $payload['pay_currency'] = $payCurrency;
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
        ])->post(rtrim($config['base_url'], '/').'/invoice', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('NOWPayments invoice creation failed: '.$response->body());
        }

        $data = $response->json();

        return new PaymentIntent(
            reference: (string) ($data['id'] ?? $order->order_number),
            checkoutUrl: $data['invoice_url'] ?? null,
            payCurrency: $payload['pay_currency'] ?? null,
            meta: $data,
        );
    }

    public function parseWebhook(Request $request): ?WebhookResult
    {
        $secret = static::secret('nowpayments_ipn_secret', 'payments.gateways.nowpayments.ipn_secret');
        $signature = $request->header('x-nowpayments-sig');

        if (! $secret || ! $signature) {
            return null;
        }

        // NOWPayments signs the JSON body with keys sorted alphabetically.
        $sorted = $request->all();
        ksort($sorted);
        $expected = hash_hmac('sha512', json_encode($sorted, JSON_UNESCAPED_SLASHES), $secret);

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $status = match ($request->input('payment_status')) {
            'finished', 'confirmed' => 'paid',
            'failed', 'refunded', 'expired' => 'failed',
            default => 'pending',
        };

        return new WebhookResult(
            reference: (string) $request->input('order_id'),
            status: $status,
            txid: (string) ($request->input('payment_id') ?? ''),
            raw: $request->all(),
        );
    }
}
