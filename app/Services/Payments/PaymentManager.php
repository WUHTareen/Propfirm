<?php

namespace App\Services\Payments;

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

    public function driver(?string $name = null): PaymentGateway
    {
        $name ??= config('payments.default', 'manual');

        if (! isset($this->drivers[$name])) {
            throw new InvalidArgumentException("Unknown payment gateway [{$name}].");
        }

        return app($this->drivers[$name]);
    }
}
