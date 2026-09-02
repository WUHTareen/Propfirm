<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Models\TradingAccount;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * The numbers the business runs on, for a given date range.
 *
 * "Revenue" here counts only orders that actually settled — an order sitting
 * at pending is not money, and counting it would flatter every report. The
 * paid states are the same ones Order::isPaid() treats as paid.
 */
class ReportService
{
    /**
     * Order statuses that represent money actually received.
     */
    public const PAID_STATUSES = ['paid', 'processing', 'completed'];

    /**
     * Withdrawal states that still represent money owed to traders — anything
     * not yet paid out and not rejected.
     */
    public const OWED_STATUSES = ['pending', 'under_review', 'approved', 'processing'];

    public function summary(Carbon $from, Carbon $to): array
    {
        $paidOrders = Order::whereIn('status', self::PAID_STATUSES)
            ->whereBetween('created_at', [$from, $to]);

        $revenue = (float) (clone $paidOrders)->sum('total');
        $orderCount = (clone $paidOrders)->count();

        return [
            'revenue' => $revenue,
            'orders_paid' => $orderCount,
            'orders_total' => Order::whereBetween('created_at', [$from, $to])->count(),
            'average_order' => $orderCount > 0 ? $revenue / $orderCount : 0.0,
            'new_traders' => User::whereBetween('created_at', [$from, $to])->count(),
            // What the firm still owes: approved payouts not yet marked paid.
            'payouts_pending' => (float) Withdrawal::whereIn('status', self::OWED_STATUSES)->sum('amount'),
            'payouts_paid' => (float) Withdrawal::where('status', 'paid')
                ->whereBetween('updated_at', [$from, $to])->sum('amount'),
        ];
    }

    /**
     * Revenue and volume split by challenge type.
     */
    public function byChallengeType(Carbon $from, Carbon $to): Collection
    {
        return Order::query()
            ->whereIn('orders.status', self::PAID_STATUSES)
            ->whereBetween('orders.created_at', [$from, $to])
            ->join('challenge_plans', 'challenge_plans.id', '=', 'orders.challenge_plan_id')
            ->selectRaw('challenge_plans.challenge_type as challenge_type, COUNT(*) as orders, SUM(orders.total) as revenue')
            ->groupBy('challenge_plans.challenge_type')
            ->orderByDesc('revenue')
            ->get();
    }

    /**
     * Revenue and volume split by account size.
     */
    public function byAccountSize(Carbon $from, Carbon $to): Collection
    {
        return Order::query()
            ->whereIn('status', self::PAID_STATUSES)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('account_size, COUNT(*) as orders, SUM(total) as revenue')
            ->groupBy('account_size')
            ->orderBy('account_size')
            ->get();
    }

    /**
     * How evaluations are ending up.
     *
     * Counted over all accounts ever assigned, not just this range — a pass
     * rate over a single week says very little, since an evaluation started in
     * that week usually finishes outside it.
     */
    public function outcomes(): array
    {
        $counts = TradingAccount::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $passed = (int) ($counts['passed'] ?? 0) + (int) ($counts['funded'] ?? 0);
        $breached = (int) ($counts['breached'] ?? 0);
        $decided = $passed + $breached;

        return [
            'active' => (int) ($counts['active'] ?? 0),
            'pending' => (int) ($counts['pending_assignment'] ?? 0),
            'passed' => $passed,
            'breached' => $breached,
            'funded' => (int) ($counts['funded'] ?? 0),
            'pass_rate' => $decided > 0 ? round($passed / $decided * 100, 1) : null,
        ];
    }

    /**
     * Rows for the CSV export — one section per block, flattened.
     */
    public function exportRows(Carbon $from, Carbon $to): array
    {
        $summary = $this->summary($from, $to);
        $outcomes = $this->outcomes();

        $rows = [
            ['Report', 'Prop firm performance'],
            ['From', $from->toDateString()],
            ['To', $to->toDateString()],
            [],
            ['Summary', ''],
            ['Revenue (paid orders)', number_format($summary['revenue'], 2)],
            ['Paid orders', $summary['orders_paid']],
            ['All orders', $summary['orders_total']],
            ['Average order value', number_format($summary['average_order'], 2)],
            ['New traders', $summary['new_traders']],
            ['Payouts owed', number_format($summary['payouts_pending'], 2)],
            ['Payouts paid in range', number_format($summary['payouts_paid'], 2)],
            [],
            ['By challenge type', 'Orders', 'Revenue'],
        ];

        foreach ($this->byChallengeType($from, $to) as $row) {
            $rows[] = [$row->challenge_type, $row->orders, number_format((float) $row->revenue, 2)];
        }

        $rows[] = [];
        $rows[] = ['By account size', 'Orders', 'Revenue'];

        foreach ($this->byAccountSize($from, $to) as $row) {
            $rows[] = ['$'.number_format((float) $row->account_size), $row->orders, number_format((float) $row->revenue, 2)];
        }

        $rows[] = [];
        $rows[] = ['Evaluation outcomes (all time)', ''];
        $rows[] = ['Active', $outcomes['active']];
        $rows[] = ['Passed or funded', $outcomes['passed']];
        $rows[] = ['Breached', $outcomes['breached']];
        $rows[] = ['Pass rate %', $outcomes['pass_rate'] ?? 'n/a'];

        return $rows;
    }
}
