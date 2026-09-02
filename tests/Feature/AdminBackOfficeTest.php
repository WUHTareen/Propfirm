<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Filament\Widgets\BusinessOverview;
use App\Models\Affiliate;
use App\Models\ChallengePlan;
use App\Models\Coupon;
use App\Models\GiveawayEntry;
use App\Models\Order;
use App\Models\TradingAccount;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\Reports\ReportService;
use App\Services\Rewards\GiveawayService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class AdminBackOfficeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        return $user;
    }

    private function trader(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('trader');

        return $user;
    }

    private function plan(array $overrides = []): ChallengePlan
    {
        return ChallengePlan::create(array_merge([
            'name' => '10K 2-Step',
            'slug' => '10k-two-step-'.uniqid(),
            'challenge_type' => 'two_step',
            'account_size' => 10000,
            'price' => 99,
            'currency' => 'USD',
            'phases' => [1, 2],
            'phase1_target_percent' => 7,
            'phase2_target_percent' => 5,
            'min_trading_days' => 4,
            'daily_drawdown_percent' => 5,
            'max_drawdown_percent' => 10,
            'drawdown_type' => 'static',
            'leverage' => 100,
            'profit_split_percent' => 80,
            'is_active' => true,
        ], $overrides));
    }

    private function order(User $user, ChallengePlan $plan, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'ORD-'.uniqid(),
            'user_id' => $user->id,
            'challenge_plan_id' => $plan->id,
            'platform' => 'mt5',
            'account_size' => $plan->account_size,
            'subtotal' => $plan->price,
            'total' => $plan->price,
            'currency' => 'USD',
            'status' => 'paid',
        ], $overrides));
    }

    // ---- Access control ----------------------------------------------------

    public function test_traders_cannot_reach_any_of_the_new_screens(): void
    {
        $trader = $this->trader();

        foreach (['/admin/coupons', '/admin/users', '/admin/certificates', '/admin/affiliates', '/admin/giveaway-entries', '/admin/reports'] as $url) {
            $this->actingAs($trader)->get($url)->assertForbidden();
        }
    }

    public function test_admin_can_open_every_new_screen(): void
    {
        $admin = $this->staff('admin');

        foreach (['/admin/coupons', '/admin/users', '/admin/certificates', '/admin/affiliates', '/admin/giveaway-entries', '/admin/reports'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_finance_staff_cannot_manage_coupons_or_users(): void
    {
        $finance = $this->staff('finance');

        // Finance handles money, not marketing codes or accounts.
        $this->actingAs($finance)->get('/admin/coupons')->assertForbidden();
        $this->actingAs($finance)->get('/admin/users')->assertForbidden();
        // But reports are part of the job.
        $this->actingAs($finance)->get('/admin/reports')->assertOk();
    }

    public function test_support_staff_can_manage_users_but_not_coupons(): void
    {
        $support = $this->staff('support');

        $this->actingAs($support)->get('/admin/users')->assertOk();
        $this->actingAs($support)->get('/admin/coupons')->assertForbidden();
    }

    // ---- Coupons -----------------------------------------------------------

    public function test_a_coupon_created_in_the_admin_applies_at_checkout(): void
    {
        $coupon = Coupon::create([
            'code' => 'LAUNCH20',
            'type' => 'percent',
            'value' => 20,
            'is_active' => true,
        ]);

        $this->assertTrue($coupon->isCurrentlyValid());
    }

    public function test_coupon_status_reflects_why_a_code_would_not_work(): void
    {
        $expired = Coupon::create([
            'code' => 'OLD', 'type' => 'percent', 'value' => 10,
            'is_active' => true, 'expires_at' => now()->subDay(),
        ]);
        $usedUp = Coupon::create([
            'code' => 'GONE', 'type' => 'fixed', 'value' => 5,
            'is_active' => true, 'max_uses' => 2, 'used_count' => 2,
        ]);
        $scheduled = Coupon::create([
            'code' => 'SOON', 'type' => 'percent', 'value' => 10,
            'is_active' => true, 'starts_at' => now()->addDay(),
        ]);

        $this->assertFalse($expired->isCurrentlyValid());
        $this->assertFalse($usedUp->isCurrentlyValid());
        $this->assertFalse($scheduled->isCurrentlyValid());
    }

    // ---- Users -------------------------------------------------------------

    public function test_an_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->staff('admin');
        $this->actingAs($admin);

        $this->assertFalse(UserResource::canDelete($admin));
        $this->assertTrue(UserResource::canDelete($this->trader()));
    }

    // ---- Giveaway ----------------------------------------------------------

    public function test_a_draw_picks_winners_and_settles_everyone_else(): void
    {
        $week = Carbon::parse('2026-09-07'); // a Monday

        foreach (range(1, 5) as $i) {
            GiveawayEntry::create([
                'user_id' => $this->trader()->id,
                'week_start' => $week,
                'status' => 'entered',
            ]);
        }

        $won = app(GiveawayService::class)->draw($week, 2, 3000);

        $this->assertSame(2, $won);
        $this->assertSame(2, GiveawayEntry::where('status', 'won')->count());
        $this->assertSame(3, GiveawayEntry::where('status', 'lost')->count());
        $this->assertSame(0, GiveawayEntry::where('status', 'entered')->count());
    }

    public function test_drawing_the_same_week_twice_hands_out_nothing_extra(): void
    {
        $week = Carbon::parse('2026-09-07');

        foreach (range(1, 3) as $i) {
            GiveawayEntry::create([
                'user_id' => $this->trader()->id,
                'week_start' => $week,
                'status' => 'entered',
            ]);
        }

        app(GiveawayService::class)->draw($week, 1, 3000);
        $secondRun = app(GiveawayService::class)->draw($week, 1, 3000);

        $this->assertSame(0, $secondRun);
        $this->assertSame(1, GiveawayEntry::where('status', 'won')->count());
    }

    public function test_a_draw_only_touches_the_week_it_was_given(): void
    {
        $thisWeek = Carbon::parse('2026-09-07');
        $nextWeek = Carbon::parse('2026-09-14');

        GiveawayEntry::create(['user_id' => $this->trader()->id, 'week_start' => $thisWeek, 'status' => 'entered']);
        GiveawayEntry::create(['user_id' => $this->trader()->id, 'week_start' => $nextWeek, 'status' => 'entered']);

        app(GiveawayService::class)->draw($thisWeek, 5, 3000);

        $this->assertSame('won', GiveawayEntry::whereDate('week_start', $thisWeek)->first()->status);
        $this->assertSame('entered', GiveawayEntry::whereDate('week_start', $nextWeek)->first()->status);
    }

    public function test_winners_are_notified(): void
    {
        $week = Carbon::parse('2026-09-07');
        $trader = $this->trader();

        GiveawayEntry::create(['user_id' => $trader->id, 'week_start' => $week, 'status' => 'entered']);

        app(GiveawayService::class)->draw($week, 1, 5000);

        $this->assertSame(1, $trader->notifications()->count());
    }

    // ---- Reports -----------------------------------------------------------

    public function test_revenue_counts_only_orders_that_were_actually_paid(): void
    {
        $plan = $this->plan(['price' => 100]);
        $trader = $this->trader();

        $this->order($trader, $plan, ['status' => 'paid', 'total' => 100]);
        $this->order($trader, $plan, ['status' => 'completed', 'total' => 200]);
        $this->order($trader, $plan, ['status' => 'pending', 'total' => 999]);
        $this->order($trader, $plan, ['status' => 'refunded', 'total' => 500]);

        $summary = app(ReportService::class)->summary(now()->subDay(), now()->addDay());

        $this->assertSame(300.0, $summary['revenue']);
        $this->assertSame(2, $summary['orders_paid']);
        $this->assertSame(4, $summary['orders_total']);
        $this->assertSame(150.0, $summary['average_order']);
    }

    public function test_orders_outside_the_range_are_excluded(): void
    {
        $plan = $this->plan();
        $trader = $this->trader();

        $old = $this->order($trader, $plan, ['total' => 100]);
        $old->forceFill(['created_at' => now()->subMonths(3)])->save();

        $this->order($trader, $plan, ['total' => 50]);

        $summary = app(ReportService::class)->summary(now()->startOfMonth(), now()->endOfDay());

        $this->assertSame(50.0, $summary['revenue']);
    }

    public function test_payouts_owed_counts_everything_not_yet_paid_or_rejected(): void
    {
        $plan = $this->plan();
        $trader = $this->trader();
        $account = TradingAccount::create([
            'user_id' => $trader->id,
            'challenge_plan_id' => $plan->id,
            'platform' => 'mt5',
            'account_size' => 10000,
            'challenge_type' => 'two_step',
            'current_phase' => 1,
            'status' => 'funded',
            'starting_balance' => 10000,
            'profit_target_amount' => 700,
            'daily_drawdown_limit' => 500,
            'max_drawdown_limit' => 1000,
        ]);

        foreach ([['pending', 100], ['under_review', 200], ['approved', 300], ['rejected', 400], ['paid', 500]] as [$status, $amount]) {
            Withdrawal::create([
                'withdrawal_number' => 'WD-'.uniqid(),
                'user_id' => $trader->id,
                'trading_account_id' => $account->id,
                'amount' => $amount,
                'method' => 'usdt_bsc',
                'wallet_address' => '0xabc',
                'status' => $status,
            ]);
        }

        $summary = app(ReportService::class)->summary(now()->subDay(), now()->addDay());

        // 100 + 200 + 300 — the rejected and already-paid ones are not owed.
        $this->assertSame(600.0, $summary['payouts_pending']);
    }

    public function test_pass_rate_ignores_evaluations_still_running(): void
    {
        $plan = $this->plan();

        $make = function (string $status) use ($plan) {
            TradingAccount::create([
                'user_id' => $this->trader()->id,
                'challenge_plan_id' => $plan->id,
                'platform' => 'mt5',
                'account_size' => 10000,
                'challenge_type' => 'two_step',
                'current_phase' => 1,
                'status' => $status,
                'starting_balance' => 10000,
                'profit_target_amount' => 700,
                'daily_drawdown_limit' => 500,
                'max_drawdown_limit' => 1000,
            ]);
        };

        $make('funded');
        $make('breached');
        $make('breached');
        $make('breached');
        $make('active');   // undecided — must not drag the rate down
        $make('pending_assignment');

        $outcomes = app(ReportService::class)->outcomes();

        $this->assertSame(1, $outcomes['passed']);
        $this->assertSame(3, $outcomes['breached']);
        $this->assertSame(1, $outcomes['active']);
        $this->assertSame(1, $outcomes['pending']);
        $this->assertSame(25.0, $outcomes['pass_rate']);
    }

    public function test_pass_rate_is_null_when_nothing_has_been_decided(): void
    {
        $this->assertNull(app(ReportService::class)->outcomes()['pass_rate']);
    }

    public function test_breakdowns_group_by_type_and_size(): void
    {
        $twoStep = $this->plan(['challenge_type' => 'two_step', 'account_size' => 10000]);
        $instant = $this->plan(['challenge_type' => 'instant', 'account_size' => 25000]);
        $trader = $this->trader();

        $this->order($trader, $twoStep, ['total' => 100, 'account_size' => 10000]);
        $this->order($trader, $twoStep, ['total' => 150, 'account_size' => 10000]);
        $this->order($trader, $instant, ['total' => 300, 'account_size' => 25000]);

        $service = app(ReportService::class);
        $byType = $service->byChallengeType(now()->subDay(), now()->addDay())->keyBy('challenge_type');

        $this->assertSame(2, (int) $byType['two_step']->orders);
        $this->assertSame(250.0, (float) $byType['two_step']->revenue);
        $this->assertSame(300.0, (float) $byType['instant']->revenue);

        $bySize = $service->byAccountSize(now()->subDay(), now()->addDay());
        $this->assertCount(2, $bySize);
    }

    public function test_csv_export_downloads_with_the_headline_numbers(): void
    {
        $plan = $this->plan();
        $this->order($this->trader(), $plan, ['total' => 250]);

        $rows = app(ReportService::class)->exportRows(now()->subDay(), now()->addDay());
        $flat = collect($rows)->map(fn ($r) => implode('|', $r))->implode("\n");

        $this->assertStringContainsString('Revenue (paid orders)|250.00', $flat);
        $this->assertStringContainsString('Evaluation outcomes', $flat);
    }

    // ---- Dashboard widget --------------------------------------------------

    public function test_the_overview_widget_renders_its_queues(): void
    {
        $plan = $this->plan();
        $trader = $this->trader();

        // A paid order with no trading account behind it yet.
        $this->order($trader, $plan, ['total' => 120]);

        $this->actingAs($this->staff('admin'));

        Livewire::test(BusinessOverview::class)
            ->assertSuccessful()
            ->assertSee('Revenue this month')
            ->assertSee('Waiting on you')
            ->assertSee('Orders awaiting fulfilment');
    }

    public function test_the_widget_is_hidden_from_anyone_without_report_access(): void
    {
        $this->actingAs($this->trader());
        $this->assertFalse(BusinessOverview::canView());

        $this->actingAs($this->staff('admin'));
        $this->assertTrue(BusinessOverview::canView());
    }

    // ---- Affiliates --------------------------------------------------------

    public function test_marking_commission_paid_moves_it_out_of_owed(): void
    {
        $affiliate = Affiliate::create([
            'user_id' => $this->trader()->id,
            'code' => 'AFF-'.uniqid(),
            'commission_rate' => 10,
            'total_commission' => 500,
            'available_commission' => 200,
            'paid_commission' => 300,
        ]);

        // Mirrors what the Mark paid action does.
        $amount = (float) $affiliate->available_commission;
        $affiliate->forceFill([
            'paid_commission' => (float) $affiliate->paid_commission + $amount,
            'available_commission' => 0,
        ])->save();

        $affiliate->refresh();
        $this->assertSame('500.00', $affiliate->paid_commission);
        $this->assertSame('0.00', $affiliate->available_commission);
        // The lifetime total is untouched by a payout.
        $this->assertSame('500.00', $affiliate->total_commission);
    }
}
