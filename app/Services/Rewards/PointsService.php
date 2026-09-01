<?php

namespace App\Services\Rewards;

use App\Models\RewardPoint;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The points ledger. Every change is an append-only reward_points row and a
 * matching update to the cached users.points_balance. 100 points = $1.00.
 */
class PointsService
{
    /**
     * Credit points to a user (earn / adjustment / transfer_in).
     */
    public function credit(User $user, int $points, string $type, string $source, string $description, array $extra = []): RewardPoint
    {
        return $this->record($user, abs($points), $type, $source, $description, $extra);
    }

    /**
     * Debit points from a user (spend / adjustment / transfer_out).
     */
    public function debit(User $user, int $points, string $type, string $source, string $description, array $extra = []): RewardPoint
    {
        return $this->record($user, -abs($points), $type, $source, $description, $extra);
    }

    /**
     * Move points from one trader to another (atomic).
     */
    public function transfer(User $from, User $to, int $points): void
    {
        $points = abs($points);

        if ($points <= 0) {
            throw ValidationException::withMessages(['points' => 'Enter a positive amount.']);
        }
        if ($from->id === $to->id) {
            throw ValidationException::withMessages(['recipient' => 'You cannot send points to yourself.']);
        }
        if ($to->is_active === false || $from->is_active === false) {
            throw ValidationException::withMessages(['recipient' => 'Both accounts must be active.']);
        }
        if ((int) $from->points_balance < $points) {
            throw ValidationException::withMessages(['points' => 'You do not have enough points.']);
        }

        DB::transaction(function () use ($from, $to, $points) {
            $this->debit($from, $points, 'transfer_out', 'transfer', "Sent to {$to->email}", ['related_user_id' => $to->id]);
            $this->credit($to, $points, 'transfer_in', 'transfer', "Received from {$from->email}", ['related_user_id' => $from->id]);
        });
    }

    protected function record(User $user, int $signedPoints, string $type, string $source, string $description, array $extra): RewardPoint
    {
        return DB::transaction(function () use ($user, $signedPoints, $type, $source, $description, $extra) {
            $user->refresh();
            $newBalance = (int) $user->points_balance + $signedPoints;

            $entry = RewardPoint::create(array_merge([
                'user_id' => $user->id,
                'type' => $type,
                'source' => $source,
                'points' => $signedPoints,
                'balance_after' => $newBalance,
                'description' => $description,
            ], $extra));

            $user->forceFill(['points_balance' => $newBalance])->save();

            return $entry;
        });
    }
}
