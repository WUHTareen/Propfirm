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

    /**
     * The most recent equity figure (falls back to balance, then starting).
     */
    public function displayEquity(): ?float
    {
        if ($this->current_equity !== null) {
            return (float) $this->current_equity;
        }
        if ($this->current_balance !== null) {
            return (float) $this->current_balance;
        }

        return $this->starting_balance !== null ? (float) $this->starting_balance : null;
    }

    /**
     * Whether we have any live metrics yet (an assigned + synced account).
     */
    public function hasLiveMetrics(): bool
    {
        return $this->current_equity !== null || $this->current_balance !== null;
    }

    /**
     * Profit made so far in account currency (equity − starting balance).
     */
    public function profitAmount(): float
    {
        if ($this->starting_balance === null) {
            return 0.0;
        }

        return round(($this->displayEquity() ?? 0) - (float) $this->starting_balance, 2);
    }

    /**
     * Progress toward the phase profit target, 0–100 (clamped).
     */
    public function profitTargetProgress(): float
    {
        if (! $this->profit_target_amount || (float) $this->profit_target_amount <= 0) {
            return 0.0;
        }

        $pct = ($this->profitAmount() / (float) $this->profit_target_amount) * 100;

        return round(max(0, min(100, $pct)), 1);
    }

    /**
     * How much of the MAX loss allowance has been consumed, 0–100.
     */
    public function maxDrawdownUsedPercent(): float
    {
        if (! $this->max_drawdown_limit || (float) $this->max_drawdown_limit <= 0 || ! $this->hasLiveMetrics()) {
            return 0.0;
        }

        $loss = max(0, (float) $this->starting_balance - ($this->displayEquity() ?? 0));

        return round(min(100, ($loss / (float) $this->max_drawdown_limit) * 100), 1);
    }

    /**
     * How much of TODAY's loss allowance has been consumed, 0–100.
     */
    public function dailyDrawdownUsedPercent(): float
    {
        $baseline = $this->day_start_balance !== null ? (float) $this->day_start_balance : (float) $this->starting_balance;

        if (! $this->daily_drawdown_limit || (float) $this->daily_drawdown_limit <= 0 || ! $this->hasLiveMetrics()) {
            return 0.0;
        }

        $loss = max(0, $baseline - ($this->displayEquity() ?? 0));

        return round(min(100, ($loss / (float) $this->daily_drawdown_limit) * 100), 1);
    }

    /**
     * Min trading days required by the plan for the current phase.
     */
    public function requiredTradingDays(): int
    {
        $phases = $this->challengePlan?->phases ?? [];
        $current = collect($phases)->firstWhere('phase', $this->current_phase);

        return (int) ($current['min_trading_days'] ?? $this->challengePlan?->min_trading_days ?? 0);
    }

    public function isCredentialed(): bool
    {
        return ! empty($this->login);
    }
}
