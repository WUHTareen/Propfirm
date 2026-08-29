<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GiveawayEntry extends Model
{
    protected $fillable = [
        'user_id', 'week_start', 'trustpilot_review_link',
        'status', 'prize_account_size', 'drawn_at',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'prize_account_size' => 'decimal:2',
            'drawn_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
