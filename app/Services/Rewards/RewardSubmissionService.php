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

    public function approve(RewardSubmission $submission, User $reviewer): void
    {
        if ($submission->status === 'approved') {
            return;
        }

        $submission->forceFill([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ])->save();

        $this->points->credit(
            $submission->user,
            $submission->points_value,
            'earn',
            $submission->type,
            'Reward: '.str_replace('_', ' ', $submission->type),
        );

        $submission->user?->notify(new TradingAccountNotification(
            'Reward approved',
            "You earned {$submission->points_value} points for your ".str_replace('_', ' ', $submission->type).'.',
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
