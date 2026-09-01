<?php

namespace App\Http\Controllers;

use App\Models\ChallengePlan;
use App\Models\RewardPoint;
use App\Models\TradingAccount;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request)
    {
        $size = $request->query('size');

        $query = TradingAccount::query()
            ->whereIn('status', ['active', 'passed', 'funded'])
            ->with('user');

        if ($size) {
            $query->where('account_size', $size);
        }

        $accounts = $query->get();

        // Rank traders by total profit across their qualifying accounts.
        $ranking = $accounts
            ->groupBy('user_id')
            ->map(function ($accs) {
                $profit = $accs->sum(fn (TradingAccount $a) => $a->profitAmount());
                $base = (float) $accs->sum('account_size');

                return [
                    'user' => $accs->first()->user,
                    'profit' => round($profit, 2),
                    'profit_pct' => $base > 0 ? round($profit / $base * 100, 2) : 0,
                ];
            })
            ->sortByDesc('profit')
            ->values();

        $sizes = ChallengePlan::active()
            ->distinct()
            ->orderBy('account_size')
            ->pluck('account_size');

        $stats = [
            'funded_traders' => TradingAccount::where('status', 'funded')->distinct('user_id')->count('user_id'),
            'top_profit' => round((float) $accounts->max(fn (TradingAccount $a) => $a->profitAmount()) ?: 0, 2),
            'points_awarded' => (int) RewardPoint::where('points', '>', 0)->sum('points'),
            'ranked_traders' => $ranking->count(),
        ];

        return view('dashboard.leaderboard', [
            'ranking' => $ranking,
            'sizes' => $sizes,
            'activeSize' => $size,
            'stats' => $stats,
        ]);
    }
}
