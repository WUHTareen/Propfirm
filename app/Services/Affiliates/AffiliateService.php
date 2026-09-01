<?php

namespace App\Services\Affiliates;

use App\Models\Affiliate;
use App\Models\Order;
use App\Models\Referral;
use App\Models\User;

/**
 * Referral / affiliate tracking: a per-user referral code, click and signup
 * attribution, and commission credited when a referred trader pays.
 */
class AffiliateService
{
    /**
     * The default commission rate (%) new affiliates start on.
     */
    public const DEFAULT_RATE = 10.0;

    public function ensureAffiliate(User $user): Affiliate
    {
        return $user->affiliate()->firstOrCreate([], [
            'code' => $user->referral_code ?: 'AFF-'.\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8)),
            'commission_rate' => self::DEFAULT_RATE,
            'is_active' => true,
        ]);
    }

    public function findByCode(string $code): ?Affiliate
    {
        return Affiliate::whereRaw('LOWER(code) = ?', [\Illuminate\Support\Str::lower($code)])->first();
    }

    public function recordClick(string $code, ?string $ip = null, ?string $landingUrl = null): void
    {
        $affiliate = $this->findByCode($code);
        if (! $affiliate) {
            return;
        }

        $affiliate->increment('clicks');
        Referral::create([
            'affiliate_id' => $affiliate->id,
            'status' => 'click',
            'ip_address' => $ip,
            'landing_url' => $landingUrl,
        ]);
    }

    /**
     * Attribute a newly-registered user to a referral code.
     */
    public function attributeSignup(User $newUser, string $code): void
    {
        $affiliate = $this->findByCode($code);
        if (! $affiliate || $affiliate->user_id === $newUser->id) {
            return;
        }

        $newUser->forceFill(['referred_by' => $affiliate->user_id])->save();
        $affiliate->increment('signups');

        Referral::create([
            'affiliate_id' => $affiliate->id,
            'referred_user_id' => $newUser->id,
            'status' => 'signup',
        ]);
    }

    /**
     * Credit the referrer's commission when a referred trader's order is paid.
     */
    public function creditCommission(Order $order): void
    {
        $buyer = $order->user;
        if (! $buyer?->referred_by) {
            return;
        }

        $affiliate = Affiliate::where('user_id', $buyer->referred_by)->first();
        if (! $affiliate || ! $affiliate->is_active) {
            return;
        }

        // One commission per order.
        if (Referral::where('order_id', $order->id)->where('status', 'converted')->exists()) {
            return;
        }

        $commission = round((float) $order->total * (float) $affiliate->commission_rate / 100, 2);

        $affiliate->increment('conversions');
        $affiliate->increment('total_commission', $commission);
        $affiliate->increment('available_commission', $commission);

        Referral::create([
            'affiliate_id' => $affiliate->id,
            'referred_user_id' => $buyer->id,
            'order_id' => $order->id,
            'status' => 'converted',
            'commission_amount' => $commission,
            'converted_at' => now(),
        ]);
    }
}
