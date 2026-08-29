<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_number', 'user_id', 'challenge_plan_id', 'plan_snapshot', 'platform',
        'account_size', 'subtotal', 'coupon_id', 'discount_amount', 'points_redeemed',
        'points_value', 'cashback_opt_in', 'total', 'currency', 'status',
        'payment_method', 'payment_gateway', 'payment_reference', 'payment_txid',
        'crypto_amount', 'crypto_currency', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'plan_snapshot' => 'array',
            'account_size' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'points_value' => 'decimal:2',
            'total' => 'decimal:2',
            'crypto_amount' => 'decimal:8',
            'cashback_opt_in' => 'boolean',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function challengePlan(): BelongsTo
    {
        return $this->belongsTo(ChallengePlan::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function tradingAccount(): HasOne
    {
        return $this->hasOne(TradingAccount::class);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['paid', 'processing', 'completed'], true);
    }
}
