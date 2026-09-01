<?php

namespace Tests\Feature;

use App\Filament\Resources\RewardSubmissionResource\Pages\ListRewardSubmissions;
use App\Models\Order;
use App\Models\RewardSubmission;
use App\Models\User;
use App\Services\Affiliates\AffiliateService;
use App\Services\Orders\OrderFulfillmentService;
use App\Services\Rewards\PointsService;
use App\Services\Rewards\RewardSubmissionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class RewardsAffiliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function trader(array $overrides = []): User
    {
        $u = User::factory()->create(array_merge(['email_verified_at' => now()], $overrides));
        $u->assignRole('trader');

        return $u;
    }

    // ---- Points ------------------------------------------------------------

    public function test_points_transfer_moves_balance_atomically(): void
    {
        $from = $this->trader(['points_balance' => 500]);
        $to = $this->trader(['points_balance' => 0]);

        app(PointsService::class)->transfer($from, $to, 200);

        $this->assertSame(300, $from->fresh()->points_balance);
        $this->assertSame(200, $to->fresh()->points_balance);
        $this->assertSame(1, $from->rewardPoints()->where('type', 'transfer_out')->count());
        $this->assertSame(1, $to->rewardPoints()->where('type', 'transfer_in')->count());
    }

    public function test_transfer_fails_without_enough_points(): void
    {
        $this->expectException(ValidationException::class);
        app(PointsService::class)->transfer($this->trader(['points_balance' => 50]), $this->trader(), 100);
    }

    // ---- Reward submissions ------------------------------------------------

    public function test_submission_is_one_time_and_approval_credits_points(): void
    {
        $trader = $this->trader(['points_balance' => 0]);
        $service = app(RewardSubmissionService::class);

        $submission = $service->submit($trader, 'video_review', 'https://example.com/v');
        $this->assertSame('pending', $submission->status);

        // One-time.
        try {
            $service->submit($trader, 'video_review', 'https://example.com/v2');
            $this->fail('Expected a duplicate submission to be rejected.');
        } catch (ValidationException) {
            // expected
        }

        $admin = $this->staffAdmin();
        Livewire::actingAs($admin)->test(ListRewardSubmissions::class)
            ->callTableAction('approve', $submission)->assertHasNoTableActionErrors();

        $this->assertSame('approved', $submission->fresh()->status);
        $this->assertSame(500, $trader->fresh()->points_balance);
    }

    private function staffAdmin(): User
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');

        return $admin;
    }

    // ---- Affiliate / referral ----------------------------------------------

    public function test_referral_link_attributes_a_new_signup(): void
    {
        $referrer = $this->trader();
        $affiliate = app(AffiliateService::class)->ensureAffiliate($referrer);

        // Visit the referral link, then register.
        $this->get('/r/'.$affiliate->code)->assertRedirect(route('register'));

        $this->post('/register', [
            'name' => 'Referred User',
            'email' => 'referred@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ]);

        $newUser = User::where('email', 'referred@example.com')->first();
        $this->assertSame($referrer->id, $newUser->referred_by);
        $this->assertSame(1, $affiliate->fresh()->signups);
    }

    public function test_commission_is_credited_when_a_referred_order_is_paid(): void
    {
        $referrer = $this->trader();
        $affiliate = app(AffiliateService::class)->ensureAffiliate($referrer); // 10% default
        $buyer = $this->trader(['referred_by' => $referrer->id]);

        $order = Order::factory()->for($buyer)->create(['status' => 'pending', 'total' => 100]);
        app(OrderFulfillmentService::class)->markPaid($order);

        $affiliate->refresh();
        $this->assertSame(1, $affiliate->conversions);
        $this->assertEquals(10.0, (float) $affiliate->available_commission);
    }

    // ---- Trader page -------------------------------------------------------

    public function test_affiliation_page_and_sharing_over_http(): void
    {
        $from = $this->trader(['points_balance' => 300]);
        $to = $this->trader();

        $this->actingAs($from)->get('/dashboard/affiliation')->assertOk()->assertSee('Points balance');

        $this->actingAs($from)->post('/dashboard/affiliation/share', [
            'recipient' => $to->email,
            'points' => 100,
        ])->assertRedirect();

        $this->assertSame(200, $from->fresh()->points_balance);
        $this->assertSame(100, $to->fresh()->points_balance);
    }
}
