<?php

namespace App\Services\Rewards;

use App\Models\RewardSubmission;
use App\Models\User;
use App\Notifications\TradingAccountNotification;
use Illuminate\Validation\ValidationException;

/**
 * Video-review and social-media reward submissions. Each is one-time per user
 * and credited (from settings) only after an admin approves it.
 */
class RewardSubmissionService
{
    public function __construct(protected PointsService $points) {}

    /**
     * Points a given submission type is worth (admin-configurable via settings).
     */
    public function pointsFor(string $type): int
    {
        return match ($type) {
            'video_review' => (int) \App\Models\Setting::get('video_review_points', 500),
            'social_media' => (int) \App\Models\Setting::get('social_media_points', 300),
            default => 0,
        };
    }

    public function submit(User $user, string $type, ?string $link = null, ?string $platform = null, ?string $description = null): RewardSubmission
    {
        // One approved/pending submission per type per user.
        $exists = $user->rewardSubmissions()
            ->where('type', $type)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages(['type' => 'You have already submitted this reward.']);
        }

        return RewardSubmission::create([
            'user_id' => $user->id,
            'type' => $type,
            'platform' => $platform,
            'link' => $link,
            'description' => $description,
            'points_value' => $this->pointsFor($type),
            'status' => 'pending',
        ]);
    }

    /**
     * "Request Reward" from the Achievement page. Unlike the one-time video
     * and social rewards, a trader may file these repeatedly — but only one at
     * a time, so a queue of duplicates can't pile up on the reviewer. The
     * amount is left at zero for the admin to set when approving.
     */
    public function submitTask(User $user, string $category, string $description, ?string $link = null): RewardSubmission
    {
        $pending = $user->rewardSubmissions()
            ->where('type', 'task')
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            throw ValidationException::withMessages([
                'category' => 'You already have a reward request awaiting review.',
            ]);
        }

        return RewardSubmission::create([
            'user_id' => $user->id,
            'type' => 'task',
            'category' => $category,
            'link' => $link,
            'description' => $description,
            'points_value' => 0,
            'status' => 'pending',
        ]);
    }

    /**
     * Approve and credit. Task rewards arrive worth nothing — the reviewer
     * decides the amount — so an explicit $points overrides what was stored.
     */
    public function approve(RewardSubmission $submission, User $reviewer, ?int $points = null): void
    {
        if ($submission->status === 'approved') {
            return;
        }

        if ($points !== null) {
            $submission->points_value = max(0, $points);
        }

        $submission->forceFill([
            'points_value' => $submission->points_value,
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ])->save();

        $this->points->credit(
            $submission->user,
            $submission->points_value,
            'earn',
            $submission->type,
            'Reward: '.($submission->category ?: str_replace('_', ' ', $submission->type)),
        );

        $submission->user?->notify(new TradingAccountNotification(
            'Reward approved',
            "You earned {$submission->points_value} points for your ".($submission->category ?: str_replace('_', ' ', $submission->type)).'.',
            0,
        ));
    }

    public function reject(RewardSubmission $submission, User $reviewer, string $remarks): void
    {
        $submission->forceFill([
            'status' => 'rejected',
            'remarks' => $remarks,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ])->save();
    }
}
