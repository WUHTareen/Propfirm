<?php

namespace App\Services\Payments;

use App\Models\Setting;
use App\Services\Payments\Contracts\PaymentGateway;
use InvalidArgumentException;

/**
 * Resolves the active payment gateway (or a named one for webhooks).
 */
class PaymentManager
{
    /** @var array<string, class-string<PaymentGateway>> */
    protected array $drivers = [
        'manual' => ManualGateway::class,
        'nowpayments' => NowPaymentsGateway::class,
    ];

    /**
     * The gateway selected in admin settings (falling back to config/.env).
     */
    public function activeName(): string
    {
        return Setting::get('payment_gateway') ?: config('payments.default', 'manual');
    }

    public function driver(?string $name = null): PaymentGateway
    {
        $name ??= $this->activeName();

        if (! isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Unknown payment gateway [{$name}].");
        }

        return app($this->drivers[$name]);
    }
}
