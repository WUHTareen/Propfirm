<?php

namespace App\Livewire;

use App\Models\ChallengePlan;
use App\Models\Coupon;
use App\Services\Checkout\CheckoutService;
use App\Services\Checkout\PricingService;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.dashboard')]
class BuyChallenge extends Component
{
    public ?string $challengeType = 'two_step';
    public ?float $accountSize = null;
    public string $platform = 'mt5';
    public string $method = 'usdt_bsc';
    public string $couponCode = '';
    public bool $redeemPoints = false;
    public bool $cashbackOptIn = false;

    public function mount(): void
    {
        // Default to the largest-selling entry size for the current type.
        $this->accountSize ??= ChallengePlan::active()
            ->where('challenge_type', $this->challengeType)
            ->orderBy('account_size')
            ->value('account_size');
    }

    public function updatedChallengeType(): void
    {
        // Keep the selected size valid for the newly chosen type.
        $sizes = $this->sizesForType();
        if (! in_array((float) $this->accountSize, array_map('floatval', $sizes), true)) {
            $this->accountSize = $sizes[0] ?? null;
        }
    }

    /** @return array<int, float> */
    protected function sizesForType(): array
    {
        return ChallengePlan::active()
            ->where('challenge_type', $this->challengeType)
            ->orderBy('account_size')
            ->pluck('account_size')
            ->map(fn ($s) => (float) $s)
            ->all();
    }

    protected function currentPlan(): ?ChallengePlan
    {
        if (! $this->accountSize) {
            return null;
        }

        return ChallengePlan::active()
            ->where('challenge_type', $this->challengeType)
            ->where('account_size', $this->accountSize)
            ->first();
    }

    public function placeOrder(CheckoutService $checkout)
    {
        $plan = $this->currentPlan();

        $this->validate([
            'platform' => 'required|in:mt5,mt4',
            'method' => 'required|string',
        ]);

        if (! $plan) {
            $this->addError('accountSize', 'Please choose a valid account size.');

            return null;
        }

        $order = $checkout->placeOrder(
            user: auth()->user(),
            plan: $plan,
            platform: $this->platform,
            method: $this->method,
            couponCode: $this->couponCode ?: null,
            pointsToRedeem: $this->redeemPoints ? (int) auth()->user()->points_balance : 0,
            cashbackOptIn: $this->cashbackOptIn,
        );

        return $this->redirect(route('dashboard.orders.pay', $order), navigate: true);
    }

    public function render()
    {
        $plan = $this->currentPlan();
        $user = auth()->user();

        $coupon = $this->couponCode
            ? Coupon::whereRaw('LOWER(code) = ?', [Str::lower($this->couponCode)])->first()
            : null;

        $pricing = app(PricingService::class);
        $breakdown = $plan
            ? $pricing->quote($plan, $coupon, $this->redeemPoints ? (int) $user->points_balance : 0, $user)
            : null;

        $couponStatus = null;
        if ($this->couponCode !== '') {
            $couponStatus = ($coupon && $plan && $pricing->couponApplies($coupon, $user, (float) $plan->price))
                ? 'valid' : 'invalid';
        }

        return view('livewire.buy-challenge', [
            'plan' => $plan,
            'breakdown' => $breakdown,
            'couponStatus' => $couponStatus,
            'sizes' => $this->sizesForType(),
            'methods' => config('payments.methods'),
            'pointsBalance' => (int) $user->points_balance,
        ]);
    }
}
