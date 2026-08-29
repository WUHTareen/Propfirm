<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquitySnapshot extends Model
{
    protected $fillable = [
        'trading_account_id', 'balance', 'equity', 'open_pnl',
        'drawdown_percent', 'daily_drawdown_percent', 'source', 'snapshot_at',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'equity' => 'decimal:2',
            'open_pnl' => 'decimal:2',
            'drawdown_percent' => 'decimal:2',
            'daily_drawdown_percent' => 'decimal:2',
            'snapshot_at' => 'datetime',
        ];
    }

    public function tradingAccount(): BelongsTo
    {
        return $this->belongsTo(TradingAccount::class);
    }
}
