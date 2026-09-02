<?php

namespace App\Services\Rewards;

use App\Models\GiveawayEntry;
use App\Models\Setting;
use App\Notifications\TradingAccountNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The weekly Trustpilot-review giveaway draw.
 *
 * Winners must be picked once and only once: a second draw of the same week
 * would hand out duplicate free accounts. So a draw only ever considers
 * entries still sitting at 'entered', and marks every entry it looked at —
 * winners as 'won', the rest as 'lost' — inside one transaction. Running it
 * twice on the same week therefore finds nothing left to draw.
 */
class GiveawayService
{
    public static function defaultWinnerCount(): int
    {
        return (int) Setting::get('giveaway_winners_per_week', 7);
    }

    public static function defaultPrizeSize(): float
    {
        return (float) Setting::get('giveaway_prize_account_size', 3000);
    }

    /**
     * Draw a week and return how many winners were picked.
     */
    public function draw(Carbon|string $weekStart, int $winners, float $prizeAccountSize): int
    {
        $week = $weekStart instanceof Carbon
            ? $weekStart->copy()->startOfDay()
            : Carbon::parse($weekStart)->startOfDay();

        $winners = max(1, $winners);

        return DB::transaction(function () use ($week, $winners, $prizeAccountSize) {
            // lockForUpdate so two admins clicking at once can't both draw.
            $entries = GiveawayEntry::query()
                ->whereDate('week_start', $week)
                ->where('status', 'entered')
                ->lockForUpdate()
                ->get();

            if ($entries->isEmpty()) {
                return 0;
            }

            $chosen = $entries->shuffle()->take($winners);
            $chosenIds = $chosen->pluck('id')->all();

            GiveawayEntry::whereIn('id', $chosenIds)->update([
                'status' => 'won',
                'prize_account_size' => $prizeAccountSize,
                'drawn_at' => now(),
            ]);

            // Everyone else in this week has now had their chance.
            GiveawayEntry::whereIn('id', $entries->pluck('id')->all())
                ->whereNotIn('id', $chosenIds)
                ->update(['status' => 'lost', 'drawn_at' => now()]);

            foreach ($chosen as $entry) {
                $entry->user?->notify(new TradingAccountNotification(
                    'You won the weekly giveaway',
                    'You have won a free $'.number_format($prizeAccountSize).' account. Our team will be in touch with your credentials.',
                    0,
                ));
            }

            return $chosen->count();
        });
    }
}
