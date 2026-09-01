<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Livewire\BuyChallenge;
use App\Models\ChallengePlan;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Services\Checkout\PricingService;
use Database\Seeders\ChallengePlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(ChallengePlanSeeder::class);
    }

    private function trader(int $points = 0): User
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'points_balance' => $points]);
        $user->assignRole('trader');

        return $user;
    }

    public function test_pricing_applies_coupon_and_points(): void
    {
        $user = $this->trader(points: 300); // = $3
        $plan = ChallengePlan::where('slug', '2-step-10000')->first(); // $99
        $coupon = Coupon::create(['code' => 'SAVE20', 'type' => 'percent', 'value' => 20, 'is_active' => true]);

        $q = app(PricingService::class)->quote($plan, $coupon, 300, $user);

        $this->assertSame(99.00, $q->subtotal);
        $this->assertSame(19.80, $q->discountAmount);
        $this->assertSame(300, $q->pointsRedeemed);
        $this->assertSame(3.00, $q->pointsValue);
        $this->assertSame(76.20, $q->total);
    }

    public function test_trader_can_place_an_order_through_the_buy_flow(): void
    {
        $user = $this->trader();

        Livewire::actingAs($user)
            ->test(BuyChallenge::class)
            ->set('challengeType', 'two_step')
            ->set('accountSize', 10000)
            ->set('platform', 'mt5')
            ->set('method', 'usdt_bsc')
            ->call('placeOrder')
            ->assertRedirect();

        $order = Order::where('user_id', $user->id)->first();
        $this->assertNotNull($order);
        $this->assertSame('pending', $order->status);
        $this->assertEquals(99.0, (float) $order->total);
    }

    public function test_orders_page_lists_the_traders_orders(): void
    {
        $user = $this->trader();
        $order = Order::factory()->for($user)->create(['order_number' => 'ORD-TEST-1']);

        $this->actingAs($user)->get('/dashboard/orders')
            ->assertOk()
            ->assertSee('ORD-TEST-1');
    }

    public function test_pay_page_is_guarded_to_the_owner(): void
    {
        $owner = $this->trader();
        $other = $this->trader();
        $order = Order::factory()->for($owner)->create(['status' => 'pending']);

        $this->actingAs($owner)->get(route('dashboard.orders.pay', $order))->assertOk();
        $this->actingAs($other)->get(route('dashboard.orders.pay', $order))->assertForbidden();
    }

    public function test_pay_page_falls_back_when_gateway_is_misconfigured(): void
    {
        // NOWPayments selected but no API key -> must not 500; falls back to manual.
        config()->set('payments.gateways.nowpayments.api_key', null);
        $owner = $this->trader();
        $order = Order::factory()->for($owner)->create(['status' => 'pending', 'payment_gateway' => 'nowpayments']);

        $this->actingAs($owner)->get(route('dashboard.orders.pay', $order))
            ->assertOk()
            ->assertSee('Complete your payment');
    }

    public function test_nowpayments_webhook_marks_order_paid_and_provisions_account(): void
    {
        config()->set('payments.gateways.nowpayments.ipn_secret', 'test-secret');

        $user = $this->trader();
        $order = Order::factory()->for($user)->create([
            'order_number' => 'ORD-PAY-1',
            'status' => 'pending',
            'total' => 99,
            'platform' => 'mt5',
            'plan_snapshot' => [
                'name' => '2-Step $10,000', 'challenge_type' => 'two_step', 'account_size' => 10000,
                'daily_drawdown_percent' => 5, 'max_drawdown_percent' => 10, 'profit_split_percent' => 80,
                'phases' => [['phase' => 1, 'profit_target_percent' => 7, 'min_trading_days' => 4]],
            ],
        ]);

        $payload = ['order_id' => 'ORD-PAY-1', 'payment_status' => 'finished', 'payment_id' => '55555'];
        ksort($payload);
        $sig = hash_hmac('sha512', json_encode($payload, JSON_UNESCAPED_SLASHES), 'test-secret');

        $this->withHeaders(['x-nowpayments-sig' => $sig])
            ->postJson('/webhooks/payment/nowpayments', $payload)
            ->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->tradingAccount);
        $this->assertSame('pending_assignment', $order->tradingAccount->status);
        $this->assertEquals(700.0, (float) $order->tradingAccount->profit_target_amount);
    }

    public function test_webhook_rejects_a_bad_signature(): void
    {
        config()->set('payments.gateways.nowpayments.ipn_secret', 'test-secret');

        $this->withHeaders(['x-nowpayments-sig' => 'wrong'])
            ->postJson('/webhooks/payment/nowpayments', ['order_id' => 'x', 'payment_status' => 'finished'])
            ->assertStatus(400);
    }

    public function test_admin_can_mark_an_order_paid_from_the_panel(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');

        $order = Order::factory()->create(['status' => 'pending']);

        Livewire::actingAs($admin)
            ->test(ListOrders::class)
            ->callTableAction('markPaid', $order, data: ['txid' => 'abc123'])
            ->assertHasNoTableActionErrors();

        $this->assertSame('paid', $order->refresh()->status);
    }

    public function test_traders_cannot_open_the_admin_orders_screen(): void
    {
        $this->actingAs($this->trader())->get('/admin/orders')->assertForbidden();
    }
}
