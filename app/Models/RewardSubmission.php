<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardSubmission extends Model
{
    protected $fillable = [
        'user_id', 'type', 'platform', 'file_path', 'link', 'description',
        'points_value', 'status', 'remarks', 'reviewed_by', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'points_value' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
