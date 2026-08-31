<?php

namespace Tests\Feature;

use App\Filament\Resources\TradingAccountResource\Pages\ListTradingAccounts;
use App\Models\ChallengePlan;
use App\Models\TradingAccount;
use App\Models\User;
use Database\Seeders\ChallengePlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TradingAccountAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChallengePlanSeeder::class);
    }

    private function staff(string $role): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $u->assignRole($role);

        return $u;
    }

    private function account(): TradingAccount
    {
        $trader = $this->staff('trader');
        $plan = ChallengePlan::where('slug', '2-step-10000')->first();

        return TradingAccount::create([
            'user_id' => $trader->id,
            'challenge_plan_id' => $plan->id,
            'platform' => 'mt5',
            'account_size' => 10000,
            'challenge_type' => 'two_step',
            'current_phase' => 1,
            'status' => 'pending_assignment',
            'starting_balance' => 10000,
            'profit_target_amount' => 700,
        ]);
    }

    public function test_only_admins_can_open_trading_accounts(): void
    {
        $this->actingAs($this->staff('admin'))->get('/admin/trading-accounts')->assertOk();
        $this->actingAs($this->staff('support'))->get('/admin/trading-accounts')->assertForbidden();
    }

    public function test_admin_assigns_credentials_and_activates(): void
    {
        $account = $this->account();

        Livewire::actingAs($this->staff('admin'))
            ->test(ListTradingAccounts::class)
            ->callTableAction('assign', $account, data: [
                'login' => '50011', 'password' => 'pw123', 'server' => 'Broker-Live',
            ])
            ->assertHasNoTableActionErrors();

        $account->refresh();
        $this->assertSame('active', $account->status);
        $this->assertSame('50011', $account->login);
        $this->assertSame('pw123', $account->password); // decrypts via cast
    }

    public function test_update_metrics_records_a_snapshot(): void
    {
        $account = $this->account();
        $account->update(['status' => 'active']);

        Livewire::actingAs($this->staff('admin'))
            ->test(ListTradingAccounts::class)
            ->callTableAction('metrics', $account, data: ['balance' => 10500, 'equity' => 10480])
            ->assertHasNoTableActionErrors();

        $account->refresh();
        $this->assertEquals(10480.0, (float) $account->current_equity);
        $this->assertSame(1, $account->equitySnapshots()->count());
    }

    public function test_pass_phase_advances_and_issues_certificate(): void
    {
        $account = $this->account();
        $account->update(['status' => 'active']);

        Livewire::actingAs($this->staff('admin'))
            ->test(ListTradingAccounts::class)
            ->callTableAction('passPhase', $account)
            ->assertHasNoTableActionErrors();

        $account->refresh();
        $this->assertSame(2, $account->current_phase);
        $this->assertSame(1, $account->user->certificates()->count());
    }

    public function test_breach_marks_account_breached(): void
    {
        $account = $this->account();
        $account->update(['status' => 'active']);

        Livewire::actingAs($this->staff('admin'))
            ->test(ListTradingAccounts::class)
            ->callTableAction('breach', $account, data: ['reason' => 'max_drawdown'])
            ->assertHasNoTableActionErrors();

        $account->refresh();
        $this->assertSame('breached', $account->status);
        $this->assertSame('max_drawdown', $account->breach_reason);
    }
}
