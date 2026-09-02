<?php

namespace App\Filament\Widgets;

use App\Models\KycDocument;
use App\Models\Order;
use App\Models\RewardSubmission;
use App\Models\TradingAccount;
use App\Models\Withdrawal;
use App\Services\Reports\ReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * What the back office should see on opening: this month's money, and the
 * queues where someone is waiting on staff to act.
 */
class BusinessOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $from = now()->startOfMonth();
        $to = now()->endOfDay();

        $summary = app(ReportService::class)->summary($from, $to);

        // Queues: paid orders with no account yet, KYC waiting, payouts waiting.
        $awaitingAccount = Order::whereIn('status', ReportService::PAID_STATUSES)
            ->whereDoesntHave('tradingAccount')
            ->count();

        $unassigned = TradingAccount::where('status', 'pending_assignment')->count();
        $kycPending = KycDocument::where('status', 'pending')->count();
        $withdrawalsPending = Withdrawal::whereIn('status', ReportService::OWED_STATUSES)->count();
        $rewardsPending = RewardSubmission::where('status', 'pending')->count();

        $needsAction = $unassigned + $kycPending + $withdrawalsPending + $rewardsPending;

        return [
            Stat::make('Revenue this month', '$'.number_format($summary['revenue'], 2))
                ->description($summary['orders_paid'].' paid orders')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('New traders this month', number_format($summary['new_traders']))
                ->description('Registered accounts')
                ->descriptionIcon('heroicon-m-user-plus'),

            Stat::make('Payouts owed', '$'.number_format($summary['payouts_pending'], 2))
                ->description($withdrawalsPending.' request'.($withdrawalsPending === 1 ? '' : 's').' open')
                ->descriptionIcon('heroicon-m-arrow-up-right')
                ->color($summary['payouts_pending'] > 0 ? 'warning' : 'gray'),

            Stat::make('Waiting on you', number_format($needsAction))
                ->description($needsAction === 0
                    ? 'Every queue is clear'
                    : "{$unassigned} accounts · {$kycPending} KYC · {$withdrawalsPending} payouts · {$rewardsPending} rewards")
                ->descriptionIcon('heroicon-m-inbox-arrow-down')
                ->color($needsAction > 0 ? 'danger' : 'success'),

            Stat::make('Orders awaiting fulfilment', number_format($awaitingAccount))
                ->description('Paid, but no trading account yet')
                ->descriptionIcon('heroicon-m-clock')
                ->color($awaitingAccount > 0 ? 'warning' : 'gray'),

            Stat::make('Funded accounts', number_format(TradingAccount::where('status', 'funded')->count()))
                ->description('Live funded traders')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->can('view reports') ?? false;
    }
}
