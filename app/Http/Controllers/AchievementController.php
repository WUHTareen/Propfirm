<?php

namespace App\Http\Controllers;

use App\Services\Rewards\RewardSubmissionService;
use Illuminate\Http\Request;

/**
 * Achievement page: the certificates a trader has earned, plus the reward
 * requests they have raised against them and where each one stands.
 *
 * Certificates are issued automatically when an account passes a phase or
 * becomes funded (see TradingAccountService) — nothing is created here.
 */
class AchievementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('dashboard.certificates', [
            'certificates' => $user->certificates()->latest('issued_at')->get(),
            'requests' => $user->rewardSubmissions()->where('type', 'task')->latest()->get(),
        ]);
    }

    /**
     * "Request Reward" — raises a task submission for an admin to review.
     * The payout amount is decided by the reviewer, not claimed by the trader.
     */
    public function requestReward(Request $request, RewardSubmissionService $rewards)
    {
        $data = $request->validate([
            'category' => 'required|string|max:80',
            'description' => 'required|string|max:1000',
            'link' => 'nullable|url|max:255',
        ]);

        $rewards->submitTask(
            $request->user(),
            $data['category'],
            $data['description'],
            $data['link'] ?? null,
        );

        return back()->with('status', 'Reward request submitted — pending admin review.');
    }
}
