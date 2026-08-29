<?php

namespace Database\Seeders;

use App\Models\ChallengePlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds a starter catalogue based on the reference platform's rules.
 * All numbers here are placeholders the client will override in the admin
 * Challenge Plan Builder — nothing about pricing/rules is hardcoded elsewhere.
 */
class ChallengePlanSeeder extends Seeder
{
    public function run(): void
    {
        $sizes = [3000, 5000, 10000, 25000, 50000, 100000, 200000];

        // Indicative fee per account size (USD). Client will set real prices.
        $prices = [
            3000 => 29, 5000 => 49, 10000 => 99, 25000 => 189,
            50000 => 299, 100000 => 499, 200000 => 999,
        ];

        $types = [
            'two_step' => [
                'label' => '2-Step',
                'phases' => [
                    ['phase' => 1, 'profit_target_percent' => 7, 'min_trading_days' => 4],
                    ['phase' => 2, 'profit_target_percent' => 5, 'min_trading_days' => 4],
                ],
                'phase1' => 7, 'phase2' => 5, 'min_days' => 4,
                'daily_dd' => 5, 'max_dd' => 10, 'price_factor' => 1.0,
            ],
            'one_step' => [
                'label' => '1-Step',
                'phases' => [
                    ['phase' => 1, 'profit_target_percent' => 10, 'min_trading_days' => 4],
                ],
                'phase1' => 10, 'phase2' => null, 'min_days' => 4,
                'daily_dd' => 4, 'max_dd' => 6, 'price_factor' => 1.15,
            ],
            'instant' => [
                'label' => 'Instant',
                'phases' => [], // funded immediately, no evaluation phase
                'phase1' => null, 'phase2' => null, 'min_days' => 0,
                'daily_dd' => 4, 'max_dd' => 8, 'price_factor' => 1.6,
            ],
        ];

        foreach ($types as $type => $cfg) {
            foreach ($sizes as $i => $size) {
                $price = round($prices[$size] * $cfg['price_factor']);
                $name = "{$cfg['label']} \${$this->money($size)}";

                ChallengePlan::updateOrCreate(
                    ['slug' => Str::slug("{$cfg['label']}-{$size}")],
                    [
                        'name' => $name,
                        'challenge_type' => $type,
                        'account_size' => $size,
                        'price' => $price,
                        'currency' => 'USD',
                        'phases' => $cfg['phases'],
                        'phase1_target_percent' => $cfg['phase1'],
                        'phase2_target_percent' => $cfg['phase2'],
                        'min_trading_days' => $cfg['min_days'],
                        'daily_drawdown_percent' => $cfg['daily_dd'],
                        'max_drawdown_percent' => $cfg['max_dd'],
                        'drawdown_type' => 'static',
                        'leverage' => 100,
                        'has_consistency_rule' => false,
                        'profit_split_percent' => 80,
                        'is_active' => true,
                        'sort_order' => $i,
                    ],
                );
            }
        }
    }

    private function money(int $amount): string
    {
        return number_format($amount);
    }
}
