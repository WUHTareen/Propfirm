<?php

namespace Tests\Feature;

use App\Models\EquitySnapshot;
use App\Models\TradingAccount;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function trader(): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $u->assignRole('trader');

        return $u;
    }

    private function account(User $user, array $overrides = []): TradingAccount
    {
        return TradingAccount::create(array_merge([
            'user_id' => $user->id,
            'platform' => 'mt5',
            'account_size' => 10000,
            'challenge_type' => 'two_step',
            'current_phase' => 1,
            'status' => 'active',
            'starting_balance' => 10000,
            'profit_target_amount' => 700,
            'daily_drawdown_limit' => 500,
            'max_drawdown_limit' => 1000,
        ], $overrides));
    }

    public function test_metric_calculations(): void
    {
        $acc = $this->account($this->trader(), ['current_equity' => 10350]);
        $this->assertSame(350.0, $acc->profitAmount());
        $this->assertSame(50.0, $acc->profitTargetProgress());
        $this->assertSame(0.0, $acc->maxDrawdownUsedPercent());

        $acc->current_equity = 9600; // $400 loss
        $this->assertSame(40.0, $acc->maxDrawdownUsedPercent());   // 400 / 1000
        $this->assertSame(80.0, $acc->dailyDrawdownUsedPercent()); // 400 / 500
    }

    public function test_pending_account_has_no_live_metrics(): void
    {
        $acc = $this->account($this->trader(), ['status' => 'pending_assignment', 'current_equity' => null, 'current_balance' => null]);
        $this->assertFalse($acc->hasLiveMetrics());
        $this->assertSame(0.0, $acc->maxDrawdownUsedPercent());
    }

    public function test_owner_sees_account_detail_others_forbidden(): void
    {
        $owner = $this->trader();
        $acc = $this->account($owner, ['current_equity' => 10200]);

        $this->actingAs($owner)->get(route('dashboard.accounts.show', $acc))
            ->assertOk()
            ->assertSee('Equity curve')
            ->assertSee('Login credentials');

        $this->actingAs($this->trader())->get(route('dashboard.accounts.show', $acc))->assertForbidden();
    }

    public function test_equity_chart_renders_with_snapshots(): void
    {
        $owner = $this->trader();
        $acc = $this->account($owner, ['current_equity' => 10400]);

        foreach ([10000, 10150, 10400] as $i => $equity) {
            EquitySnapshot::create([
                'trading_account_id' => $acc->id,
                'balance' => $equity,
                'equity' => $equity,
                'snapshot_at' => now()->subDays(3 - $i),
            ]);
        }

        $this->actingAs($owner)->get(route('dashboard.accounts.show', $acc))
            ->assertOk()
            ->assertSee('<polyline', false);
    }

    public function test_credentials_show_when_assigned(): void
    {
        $owner = $this->trader();
        $acc = $this->account($owner, ['login' => '5001234', 'server' => 'Broker-Live', 'password' => 'secretpass']);

        $this->actingAs($owner)->get(route('dashboard.accounts.show', $acc))
            ->assertOk()
            ->assertSee('5001234')
            ->assertSee('Broker-Live');
    }
}
