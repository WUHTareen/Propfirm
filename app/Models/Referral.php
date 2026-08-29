<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referral extends Model
{
    protected $fillable = [
        'affiliate_id', 'referred_user_id', 'order_id', 'status',
        'ip_address', 'landing_url', 'commission_amount', 'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'commission_amount' => 'decimal:2',
            'converted_at' => 'datetime',
        ];
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function referredUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
