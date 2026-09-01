<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Affiliates\AffiliateService;
use App\Services\Rewards\PointsService;
use App\Services\Rewards\RewardSubmissionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AffiliationController extends Controller
{
    /**
     * Public referral entry point: /r/CODE.
     */
    public function referral(string $code, Request $request, AffiliateService $affiliates)
    {
        $affiliates->recordClick($code, $request->ip(), $request->fullUrl());
        session(['referral_code' => $code]);

        return redirect()->route('register');
    }

    public function index(Request $request, AffiliateService $affiliates, RewardSubmissionService $rewards)
    {
        $user = $request->user();
        $affiliate = $affiliates->ensureAffiliate($user);

        return view('dashboard.affiliation', [
            'user' => $user,
            'affiliate' => $affiliate,
            'ledger' => $user->rewardPoints()->latest()->limit(20)->get(),
            'submissions' => $user->rewardSubmissions()->latest()->get()->keyBy('type'),
            'referralUrl' => url('/r/'.$affiliate->code),
            'videoPoints' => $rewards->pointsFor('video_review'),
            'socialPoints' => $rewards->pointsFor('social_media'),
        ]);
    }

    public function share(Request $request, PointsService $points)
    {
        $data = $request->validate([
            'recipient' => 'required|email',
            'points' => 'required|integer|min:1',
        ]);

        $recipient = User::where('email', $data['recipient'])->first();
        if (! $recipient) {
            throw ValidationException::withMessages(['recipient' => 'No trader found with that email.']);
        }

        $points->transfer($request->user(), $recipient, (int) $data['points']);

        return back()->with('status', "Sent {$data['points']} points to {$recipient->email}.");
    }

    public function reward(Request $request, RewardSubmissionService $rewards)
    {
        $data = $request->validate([
            'type' => 'required|in:video_review,social_media',
            'platform' => 'nullable|in:instagram,tiktok,facebook',
            'link' => 'required|url',
            'description' => 'nullable|string|max:500',
        ]);

        $rewards->submit(
            $request->user(),
            $data['type'],
            $data['link'],
            $data['platform'] ?? null,
            $data['description'] ?? null,
        );

        return back()->with('status', 'Submission received — pending admin review.');
    }
}
