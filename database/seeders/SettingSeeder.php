<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Default admin-editable global settings. Real values (pixels, chat ids,
 * wallet addresses) are entered by the client in the admin Content Manager.
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            // Brand / contact
            ['site_name', 'Prop Firm', 'general'],
            ['support_email', 'support@example.com', 'general'],
            ['points_per_dollar', 100, 'rewards'],       // 100 points = $1.00
            ['cashback_points_per_dollar', 10, 'rewards'], // earn 10 pts per $1 spent

            // Reward amounts (points)
            ['video_review_points', 500, 'rewards'],
            ['social_media_points', 300, 'rewards'],

            // Tracking / widgets (blank until client provides)
            ['facebook_pixel_id', '', 'tracking'],
            ['google_analytics_id', '', 'tracking'],
            ['tawk_to_id', '', 'widgets'],

            // Payments — supported crypto methods
            ['crypto_methods', ['usdt_bsc', 'usdt_trc20', 'usdt_erc20', 'usdt_sol', 'btc', 'usdc_eth'], 'payments'],
            ['payment_gateway', 'nowpayments', 'payments'],
        ];

        foreach ($defaults as [$key, $value, $group]) {
            Setting::set($key, $value, $group);
        }
    }
}
