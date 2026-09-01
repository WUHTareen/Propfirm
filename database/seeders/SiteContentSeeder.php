<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Default marketing-website content. Everything here is admin-editable in the
 * Content Manager — nothing on the public site is hardcoded. Values mirror the
 * reference platform; the client overrides them with their own copy.
 */
class SiteContentSeeder extends Seeder
{
    public function run(): void
    {
        $content = [
            // --- Hero -------------------------------------------------------
            ['hero_badge', 'Trade. Pass. Get funded.', 'content'],
            ['hero_title', 'Prove your edge. Trade our capital.', 'content'],
            ['hero_subtitle', 'Buy an evaluation, hit the target while respecting the rules, and earn a funded account with up to an 80% profit split. All trading on MetaTrader 5 & 4.', 'content'],

            // --- Feature highlights ----------------------------------------
            ['features', [
                ['title' => 'Up to 80% profit split', 'body' => 'Keep the lion\'s share of what you make on a funded account.'],
                ['title' => 'Fast crypto payouts', 'body' => 'Withdraw to USDT, BTC or USDC once you are funded and verified.'],
                ['title' => 'MT5 & MT4', 'body' => 'Trade on the platforms you already know, on desktop and mobile.'],
                ['title' => 'Clear, fixed rules', 'body' => 'Transparent drawdown and target rules — no hidden gotchas.'],
            ], 'content'],

            // --- How it works ----------------------------------------------
            ['how_it_works', [
                ['step' => 1, 'title' => 'Buy a challenge', 'body' => 'Pick an account size and challenge type and pay with crypto.'],
                ['step' => 2, 'title' => 'Get your account', 'body' => 'We issue your MT5/MT4 login and you download the platform.'],
                ['step' => 3, 'title' => 'Pass the evaluation', 'body' => 'Hit the profit target inside the drawdown and trading-day rules.'],
                ['step' => 4, 'title' => 'Get funded & paid', 'body' => 'Complete KYC, trade the funded account and request payouts.'],
            ], 'content'],

            // --- Trading rules (Guideline / Trading Rules pages) -----------
            ['prohibited_rules', [
                ['title' => 'High-Frequency Trading (HFT)', 'body' => 'No opening and closing a position within 15 seconds.'],
                ['title' => 'Grid Trading', 'body' => 'No 3 or more trades on the same pair in the same direction simultaneously.'],
                ['title' => 'Hedging', 'body' => 'No holding a buy and a sell on the same pair at the same time.'],
                ['title' => 'EA / Bot Misuse', 'body' => 'No exploiting price gaps or low-liquidity periods such as rollover.'],
                ['title' => 'System Exploitation & Abuse', 'body' => 'No exploiting platform errors, latency or weaknesses. Changing the MT5/MT4 password to block monitoring is prohibited.'],
                ['title' => 'Martingale Strategy', 'body' => 'No increasing trade size after losses to recover them.'],
                ['title' => 'Inactivity', 'body' => 'Accounts must remain active and traded regularly.'],
                ['title' => 'Account Sharing', 'body' => 'Accounts are for individual use only.'],
            ], 'content'],
            ['allowed_rules', [
                ['title' => 'News Trading', 'body' => 'Trading around news events is allowed unless stated otherwise for your plan.'],
                ['title' => 'Overnight & Weekend Holding', 'body' => 'Positions may be held overnight and over the weekend.'],
                ['title' => 'Expert Advisors', 'body' => 'EAs are allowed for legitimate strategy automation, within the prohibited-activity rules.'],
                ['title' => 'All Major Instruments', 'body' => 'Trade forex, indices, metals and crypto available on your platform.'],
            ], 'content'],

            // --- About ------------------------------------------------------
            ['about_heading', 'A prop firm built for serious traders'],
            ['about_body', "We back disciplined traders with real capital. Our evaluation is simple and transparent: prove you can hit a target while managing risk, and we fund you.\n\nWe are not a broker and we are not a trading platform. All trading happens on MetaTrader 5 and MetaTrader 4. Our job is to evaluate you fairly, fund the traders who pass, and pay out reliably."],

            // --- Contact ----------------------------------------------------
            ['contact_email', 'support@example.com', 'content'],
            ['contact_telegram', '', 'content'],
            ['contact_whatsapp', '', 'content'],
            ['contact_address', '', 'content'],

            // --- Social proof ----------------------------------------------
            ['trustpilot_url', '', 'content'],

            // --- Legal ------------------------------------------------------
            ['legal_terms', "These Terms govern your use of our evaluation services. By purchasing a challenge you agree to the trading rules published on this site, which may be updated from time to time.\n\nEvaluation accounts are simulated. Funded accounts trade under the arrangement described at purchase. Profit splits are paid according to the plan you bought once eligibility (funded status, KYC and minimum trading days) is met.\n\n[Replace this placeholder with your firm's full Terms of Service.]"],
            ['legal_privacy', "We collect the information you provide at sign-up and checkout, and the data needed to operate your account and process payouts (including KYC documents where required).\n\nWe do not sell your personal data. KYC documents are stored securely and used only for identity verification.\n\n[Replace this placeholder with your firm's full Privacy Policy.]"],
            ['legal_refund', "Challenge fees are generally non-refundable once an evaluation account has been issued, except where required by law.\n\nTrading carries substantial risk. Evaluation results do not guarantee funded-account performance. Only participate with fees you can afford.\n\n[Replace this placeholder with your firm's full Refund & Risk Disclosure.]"],
        ];

        foreach ($content as $row) {
            [$key, $value] = $row;
            $group = $row[2] ?? 'content';
            Setting::set($key, $value, $group);
        }
    }
}
