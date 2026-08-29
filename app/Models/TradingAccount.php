<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TradingAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'order_id', 'challenge_plan_id', 'login', 'password',
        'investor_password', 'server', 'platform', 'account_size', 'challenge_type',
        'current_phase', 'status', 'starting_balance', 'current_balance', 'current_equity',
        'profit_target_amount', 'daily_drawdown_limit', 'max_drawdown_limit',
        'highest_balance', 'day_start_balance', 'day_start_at', 'trading_days_count',
        'profit_split_percent', 'assigned_at', 'passed_at', 'funded_at', 'breached_at',
        'breach_reason', 'metaapi_account_id', 'last_synced_at',
    ];

    /**
     * Broker credentials are encrypted at rest and hidden from serialization.
     */
    protected $hidden = [
        'password', 'investor_password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'investor_password' => 'encrypted',
            'account_size' => 'decimal:2',
            'starting_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'current_equity' => 'decimal:2',
            'profit_target_amount' => 'decimal:2',
            'daily_drawdown_limit' => 'decimal:2',
            'max_drawdown_limit' => 'decimal:2',
            'highest_balance' => 'decimal:2',
            'day_start_balance' => 'decimal:2',
            'profit_split_percent' => 'decimal:2',
            'current_phase' => 'integer',
            'trading_days_count' => 'integer',
            'assigned_at' => 'datetime',
            'passed_at' => 'datetime',
            'funded_at' => 'datetime',
            'breached_at' => 'datetime',
            'day_start_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function challengePlan(): BelongsTo
    {
        return $this->belongsTo(ChallengePlan::class);
    }

    public function equitySnapshots(): HasMany
    {
        return $this->hasMany(EquitySnapshot::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * Profit made so far as a percentage of the starting balance.
     */
    public function profitPercent(): float
    {
        if (! $this->starting_balance || (float) $this->starting_balance === 0.0) {
            return 0.0;
        }

        $current = (float) ($this->current_equity ?? $this->current_balance ?? $this->starting_balance);

        return round((($current - (float) $this->starting_balance) / (float) $this->starting_balance) * 100, 2);
    }
}
