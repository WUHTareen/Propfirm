<?php

namespace App\Services\Accounts;

use App\Models\Certificate;
use App\Models\EquitySnapshot;
use App\Models\TradingAccount;
use App\Notifications\TradingAccountNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Back-office operations on a trading account: issuing credentials, updating
 * live metrics, advancing phases (with certificate + notification), and
 * recording a breach. All state transitions live here so the admin panel and
 * any future automation share one implementation.
 */
class AccountManager
{
    /**
     * Issue MT5/MT4 credentials and activate a pending account.
     *
     * @param  array{login:string,password:?string,server:?string,investor_password:?string}  $creds
     */
    public function assignCredentials(TradingAccount $account, array $creds): TradingAccount
    {
        $account->forceFill([
            'login' => $creds['login'],
            'password' => $creds['password'] ?? $account->password,
            'server' => $creds['server'] ?? $account->server,
            'investor_password' => $creds['investor_password'] ?? $account->investor_password,
            'status' => $account->status === 'pending_assignment' ? 'active' : $account->status,
            'assigned_at' => $account->assigned_at ?? now(),
        ])->save();

        $this->notify($account, 'Account ready', 'Your trading account credentials are ready. Log in on MetaTrader to begin.');

        return $account->refresh();
    }

    /**
     * Update the account's balance/equity and record an equity snapshot
     * (this is what powers the trader's equity chart and drawdown metrics).
     */
    public function updateMetrics(TradingAccount $account, float $balance, float $equity, ?float $dayStartBalance = null): EquitySnapshot
    {
        return DB::transaction(function () use ($account, $balance, $equity, $dayStartBalance) {
            $account->forceFill([
                'current_balance' => $balance,
                'current_equity' => $equity,
                'day_start_balance' => $dayStartBalance ?? $account->day_start_balance ?? $account->starting_balance,
                'highest_balance' => max((float) ($account->highest_balance ?? 0), $balance),
                'last_synced_at' => now(),
            ])->save();

            return EquitySnapshot::create([
                'trading_account_id' => $account->id,
                'balance' => $balance,
                'equity' => $equity,
                'source' => 'manual',
                'snapshot_at' => now(),
            ]);
        });
    }

    /**
     * Advance the account: pass the current phase (issuing a certificate), or
     * fund it when the final phase is cleared.
     */
    public function passPhase(TradingAccount $account): TradingAccount
    {
        $phases = $account->challengePlan?->phases ?? [];
        $totalPhases = max(1, count($phases));

        return DB::transaction(function () use ($account, $phases, $totalPhases) {
            if ($account->current_phase < $totalPhases) {
                $next = $account->current_phase + 1;
                $nextTarget = collect($phases)->firstWhere('phase', $next)['profit_target_percent'] ?? null;

                $account->forceFill([
                    'current_phase' => $next,
                    'status' => 'active',
                    // Fresh phase: reset the balance and target.
                    'current_balance' => $account->starting_balance,
                    'current_equity' => $account->starting_balance,
                    'day_start_balance' => $account->starting_balance,
                    'trading_days_count' => 0,
                    'profit_target_amount' => $nextTarget
                        ? round((float) $account->starting_balance * $nextTarget / 100, 2)
                        : null,
                ])->save();

                $this->issueCertificate($account, 'phase_pass', "Phase {$account->current_phase} reached");
                $this->notify($account, 'Phase passed', "Congratulations — you've advanced to Phase {$account->current_phase}.");
            } else {
                $account->forceFill([
                    'status' => 'funded',
                    'passed_at' => now(),
                    'funded_at' => now(),
                ])->save();

                $this->issueCertificate($account, 'funded', 'Funded account awarded');
                $this->notify($account, 'You are funded!', 'You passed the evaluation. Complete KYC to request payouts.');
            }

            return $account->refresh();
        });
    }

    /**
     * Mark the account breached with a reason.
     */
    public function breach(TradingAccount $account, string $reason): TradingAccount
    {
        $account->forceFill([
            'status' => 'breached',
            'breached_at' => now(),
            'breach_reason' => $reason,
        ])->save();

        $this->notify($account, 'Account breached', "Your account was breached: {$reason}");

        return $account->refresh();
    }

    protected function issueCertificate(TradingAccount $account, string $type, string $title): Certificate
    {
        return Certificate::create([
            'certificate_number' => 'CERT-'.now()->format('Y').'-'.Str::upper(Str::random(6)),
            'user_id' => $account->user_id,
            'trading_account_id' => $account->id,
            'type' => $type,
            'title' => $title,
            'issued_at' => now(),
            'metadata' => ['account_size' => (float) $account->account_size, 'phase' => $account->current_phase],
        ]);
    }

    protected function notify(TradingAccount $account, string $title, string $message): void
    {
        $account->user?->notify(new TradingAccountNotification($title, $message, $account->id));
    }
}
