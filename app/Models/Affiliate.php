<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model
{
    protected $fillable = [
        'user_id', 'code', 'clicks', 'signups', 'conversions', 'commission_rate',
        'total_commission', 'available_commission', 'paid_commission', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'clicks' => 'integer',
            'signups' => 'integer',
            'conversions' => 'integer',
            'commission_rate' => 'decimal:2',
            'total_commission' => 'decimal:2',
            'available_commission' => 'decimal:2',
            'paid_commission' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }
}
