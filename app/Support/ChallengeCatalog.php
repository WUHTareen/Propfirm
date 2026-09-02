<?php

namespace App\Support;

use App\Models\ChallengePlan;

/**
 * Challenge-type labels and the representative plan behind each one.
 *
 * The public Trading Rules page and the in-dashboard Guideline page show the
 * same rules to logged-out visitors and logged-in traders, so the lookup lives
 * here rather than being duplicated in both controllers. Everything is derived
 * from the Challenge Plan Builder — nothing about targets or limits is
 * hardcoded.
 */
class ChallengeCatalog
{
    /**
     * Labels for the three challenge types, in display order.
     */
    public const TYPES = [
        'two_step' => '2-Step',
        'one_step' => '1-Step',
        'instant' => 'Instant',
    ];

    /**
     * Only the types that actually have an active plan behind them.
     */
    public static function availableTypes(): array
    {
        $present = ChallengePlan::active()->distinct()->pluck('challenge_type')->all();

        return array_filter(self::TYPES, fn ($type) => in_array($type, $present, true), ARRAY_FILTER_USE_KEY);
    }

    /**
     * One representative plan per available type, keyed by type. Rules are the
     * same across account sizes, so the smallest active plan speaks for all.
     */
    public static function plansByType(): array
    {
        $out = [];

        foreach (array_keys(self::availableTypes()) as $type) {
            $plan = ChallengePlan::active()->where('challenge_type', $type)->orderBy('account_size')->first();

            if ($plan) {
                $out[$type] = $plan;
            }
        }

        return $out;
    }
}
