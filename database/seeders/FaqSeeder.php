<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['What is a prop firm challenge?', 'A challenge is an evaluation where you trade on a simulated account and must reach a profit target while respecting the drawdown and trading-day rules. Pass it and you receive a funded account.', 'general'],
            ['Where do I actually trade?', 'All trading happens on the MetaTrader 5 or MetaTrader 4 apps using the login we issue after purchase. This website is for buying challenges and tracking your progress.', 'general'],
            ['How do I receive my account credentials?', 'After your payment is confirmed, an admin assigns your MT5/MT4 login. You will be notified and can download the platform from the Downloads section of your dashboard.', 'accounts'],
            ['What payment methods do you accept?', 'We accept crypto payments including USDT (BSC, TRC20, ERC20, SOL), BTC and USDC (ETH).', 'payments'],
            ['How do payouts work?', 'Once funded and KYC-verified, you can request a withdrawal from your dashboard. After eligibility review, your agreed profit share is paid to your wallet.', 'payouts'],
            ['When can I apply for KYC?', 'KYC unlocks only after you have a funded account. Until then the KYC section stays locked.', 'kyc'],
        ];

        foreach ($faqs as $i => [$q, $a, $cat]) {
            Faq::updateOrCreate(
                ['question' => $q],
                ['answer' => $a, 'category' => $cat, 'sort_order' => $i, 'is_active' => true],
            );
        }
    }
}
