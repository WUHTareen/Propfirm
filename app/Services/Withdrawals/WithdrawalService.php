<?php

namespace App\Services\Withdrawals;

use App\Models\TradingAccount;
use App\Models\User;
use App\Models\Withdrawal;
use App\Notifications\TradingAccountNotification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WithdrawalService
{
    /**
     * The profit currently available to withdraw on a funded account.
     */
    public function availableProfit(TradingAccount $account): float
    {
        $profit = ($account->displayEquity() ?? 0) - (float) ($account->starting_balance ?? 0);

        return round(max(0, $profit), 2);
    }

    /**
     * Whether the account is eligible for a payout right now.
     */
    public function isEligible(TradingAccount $account): bool
    {
        return $account->status === 'funded'
            && $account->user?->kyc_status === 'approved'
            && $this->availableProfit($account) > 0;
    }

    public function request(User $user, TradingAccount $account, float $amount, string $method, string $wallet, ?string $network = null): Withdrawal
    {
        if ($account->user_id !== $user->id) {
            throw ValidationException::withMessages(['account' => 'That account is not yours.']);
        }

        if (! $this->isEligible($account)) {
            throw ValidationException::withMessages(['account' => 'This account is not eligible for a payout yet (needs funded status, approved KYC and available profit).']);
        }

        if ($amount <= 0 || $amount > $this->availableProfit($account)) {
            throw ValidationException::withMessages(['amount' => 'Amount must be between $0 and your available profit.']);
        }

        return Withdrawal::create([
            'withdrawal_number' => $this->uniqueNumber(),
            'user_id' => $user->id,
            'trading_account_id' => $account->id,
            'amount' => $amount,
            'method' => $method,
            'wallet_address' => $wallet,
            'network' => $network,
            'status' => 'pending',
            'eligibility_snapshot' => [
                'kyc_status' => $user->kyc_status,
                'account_status' => $account->status,
                'phase' => $account->current_phase,
                'trading_days' => $account->trading_days_count,
                'available_profit' => $this->availableProfit($account),
            ],
        ]);
    }

    public function approve(Withdrawal $withdrawal, User $reviewer): Withdrawal
    {
        $withdrawal->forceFill([
            'status' => 'approved',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ])->save();

        $this->notify($withdrawal, 'Withdrawal approved', "Your withdrawal {$withdrawal->withdrawal_number} was approved and is being processed.");

        return $withdrawal;
    }

    public function markPaid(Withdrawal $withdrawal, ?string $txid = null): Withdrawal
    {
        $withdrawal->forceFill([
            'status' => 'paid',
            'transaction_reference' => $txid ?: $withdrawal->transaction_reference,
            'paid_at' => now(),
        ])->save();

        $this->notify($withdrawal, 'Payout sent', "Your withdrawal {$withdrawal->withdrawal_number} has been paid.");

        return $withdrawal;
    }

    public function reject(Withdrawal $withdrawal, User $reviewer, string $remarks): Withdrawal
    {
        $withdrawal->forceFill([
            'status' => 'rejected',
            'remarks' => $remarks,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ])->save();

        $this->notify($withdrawal, 'Withdrawal rejected', "Your withdrawal {$withdrawal->withdrawal_number} was rejected: {$remarks}");

        return $withdrawal;
    }

    protected function notify(Withdrawal $withdrawal, string $title, string $message): void
    {
        $withdrawal->user?->notify(new TradingAccountNotification($title, $message, (int) $withdrawal->trading_account_id));
    }

    protected function uniqueNumber(): string
    {
        do {
            $number = 'WD-'.now()->format('Y').'-'.Str::upper(Str::random(6));
        } while (Withdrawal::where('withdrawal_number', $number)->exists());

        return $number;
    }
}
