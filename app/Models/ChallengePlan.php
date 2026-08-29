<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChallengePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'challenge_type', 'account_size', 'price', 'currency',
        'phases', 'phase1_target_percent', 'phase2_target_percent', 'min_trading_days',
        'daily_drawdown_percent', 'max_drawdown_percent', 'drawdown_type', 'leverage',
        'has_consistency_rule', 'consistency_percent', 'profit_split_percent',
        'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'phases' => 'array',
            'account_size' => 'decimal:2',
            'price' => 'decimal:2',
            'phase1_target_percent' => 'decimal:2',
            'phase2_target_percent' => 'decimal:2',
            'daily_drawdown_percent' => 'decimal:2',
            'max_drawdown_percent' => 'decimal:2',
            'consistency_percent' => 'decimal:2',
            'profit_split_percent' => 'decimal:2',
            'has_consistency_rule' => 'boolean',
            'is_active' => 'boolean',
            'leverage' => 'integer',
            'min_trading_days' => 'integer',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function tradingAccounts(): HasMany
    {
        return $this->hasMany(TradingAccount::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
