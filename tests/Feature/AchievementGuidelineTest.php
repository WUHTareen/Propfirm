<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\ChallengePlan;
use App\Models\RewardSubmission;
use App\Models\Setting;
use App\Models\User;
use App\Services\Rewards\RewardSubmissionService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementGuidelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function trader(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge(['email_verified_at' => now()], $overrides));
        $user->assignRole('trader');

        return $user;
    }

    private function plan(array $overrides = []): ChallengePlan
    {
        return ChallengePlan::create(array_merge([
            'name' => '10K 2-Step',
            'slug' => '10k-two-step',
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
            'has_consistency_rule' => false,
            'profit_split_percent' => 80,
            'is_active' => true,
        ], $overrides));
    }

    // ---- Achievement -------------------------------------------------------

    public function test_achievement_page_requires_login(): void
    {
        $this->get('/dashboard/certificates')->assertRedirect('/login');
    }

    public function test_empty_state_points_the_trader_at_buying_a_challenge(): void
    {
        $this->actingAs($this->trader())
            ->get('/dashboard/certificates')
            ->assertOk()
            ->assertSee('No certificates yet')
            ->assertSee('Buy a challenge');
    }

    public function test_certificates_are_listed(): void
    {
        $trader = $this->trader();

        Certificate::create([
            'certificate_number' => 'CERT-000123',
            'user_id' => $trader->id,
            'type' => 'payout',
            'title' => 'Payout milestone',
            'amount' => 1250.50,
            'issued_at' => now(),
        ]);

        $this->actingAs($trader)
            ->get('/dashboard/certificates')
            ->assertOk()
            ->assertSee('CERT-000123')
            ->assertSee('Payout milestone')
            ->assertSee('1,250.50');
    }

    public function test_a_trader_only_sees_their_own_certificates(): void
    {
        $mine = $this->trader();
        $theirs = $this->trader();

        Certificate::create([
            'certificate_number' => 'CERT-SOMEONE-ELSE',
            'user_id' => $theirs->id,
            'type' => 'funded',
            'title' => 'Funded account',
            'issued_at' => now(),
        ]);

        $this->actingAs($mine)
            ->get('/dashboard/certificates')
            ->assertOk()
            ->assertDontSee('CERT-SOMEONE-ELSE');
    }

    public function test_request_reward_creates_a_pending_submission(): void
    {
        $trader = $this->trader();

        $this->actingAs($trader)
            ->post('/dashboard/certificates/reward', [
                'category' => 'Payout milestone',
                'description' => 'Hit my first payout on the 50K account.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reward_submissions', [
            'user_id' => $trader->id,
            'type' => 'task',
            'category' => 'Payout milestone',
            'status' => 'pending',
            'points_value' => 0,
        ]);
    }

    public function test_request_reward_validates_its_input(): void
    {
        $this->actingAs($this->trader())
            ->post('/dashboard/certificates/reward', ['category' => '', 'description' => ''])
            ->assertSessionHasErrors(['category', 'description']);
    }

    public function test_only_one_reward_request_can_be_pending_at_a_time(): void
    {
        $trader = $this->trader();

        $this->actingAs($trader)->post('/dashboard/certificates/reward', [
            'category' => 'First', 'description' => 'One.',
        ]);

        $this->actingAs($trader)
            ->post('/dashboard/certificates/reward', [
                'category' => 'Second', 'description' => 'Two.',
            ])
            ->assertSessionHasErrors('category');

        $this->assertSame(1, RewardSubmission::where('user_id', $trader->id)->count());
    }

    public function test_a_reviewed_request_frees_the_trader_to_file_another(): void
    {
        $trader = $this->trader();
        $admin = $this->trader();

        $first = app(RewardSubmissionService::class)->submitTask($trader, 'First', 'One.');
        app(RewardSubmissionService::class)->reject($first, $admin, 'Not eligible yet.');

        $this->actingAs($trader)
            ->post('/dashboard/certificates/reward', [
                'category' => 'Second', 'description' => 'Two.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, RewardSubmission::where('user_id', $trader->id)->count());
    }

    public function test_approving_a_request_credits_the_amount_the_admin_sets(): void
    {
        $trader = $this->trader();
        $admin = $this->trader();

        $request = app(RewardSubmissionService::class)->submitTask($trader, 'Payout milestone', 'Details.');
        $this->assertSame(0, $request->points_value);

        app(RewardSubmissionService::class)->approve($request, $admin, 750);

        $this->assertDatabaseHas('reward_submissions', [
            'id' => $request->id,
            'status' => 'approved',
            'points_value' => 750,
        ]);
        $this->assertSame(750, $trader->fresh()->points_balance);
    }

    public function test_history_shows_status_remarks_and_amount(): void
    {
        $trader = $this->trader();
        $admin = $this->trader();

        $approved = app(RewardSubmissionService::class)->submitTask($trader, 'Payout milestone', 'Details.');
        app(RewardSubmissionService::class)->approve($approved, $admin, 500);

        $rejected = app(RewardSubmissionService::class)->submitTask($trader, 'Second try', 'Details.');
        app(RewardSubmissionService::class)->reject($rejected, $admin, 'Missing proof.');

        $this->actingAs($trader)
            ->get('/dashboard/certificates')
            ->assertOk()
            ->assertSee('Payout milestone')
            ->assertSee('Approved')
            // 500 points = $5.00
            ->assertSee('$5.00')
            ->assertSee('Rejected')
            ->assertSee('Missing proof.');
    }

    // ---- Guideline ---------------------------------------------------------

    public function test_guideline_requires_login(): void
    {
        $this->get('/dashboard/guideline')->assertRedirect('/login');
    }

    public function test_guideline_shows_rules_from_the_plan_config(): void
    {
        $this->plan(['phase1_target_percent' => 8, 'max_drawdown_percent' => 12, 'leverage' => 50]);

        $this->actingAs($this->trader())
            ->get('/dashboard/guideline')
            ->assertOk()
            ->assertSee('2-Step')
            ->assertSee('8%')
            ->assertSee('12%')
            ->assertSee('1:50');
    }

    public function test_guideline_shows_prohibited_and_allowed_activities(): void
    {
        $this->plan();

        Setting::set('prohibited_rules', [
            ['title' => 'Grid Trading', 'body' => 'No 3+ trades on the same pair in the same direction.'],
        ]);
        Setting::set('allowed_rules', [
            ['title' => 'News Trading', 'body' => 'Trading the news is permitted.'],
        ]);

        $this->actingAs($this->trader())
            ->get('/dashboard/guideline')
            ->assertOk()
            ->assertSee('Grid Trading')
            ->assertSee('No 3+ trades on the same pair in the same direction.')
            ->assertSee('News Trading');
    }

    public function test_guideline_holds_up_with_no_active_plans(): void
    {
        $this->actingAs($this->trader())
            ->get('/dashboard/guideline')
            ->assertOk()
            ->assertSee('No challenge plans are active yet');
    }

    public function test_guideline_and_the_public_rules_page_agree(): void
    {
        $this->plan(['phase1_target_percent' => 9]);

        $this->get('/trading-rules')->assertOk()->assertSee('9%');

        $this->actingAs($this->trader())
            ->get('/dashboard/guideline')
            ->assertOk()
            ->assertSee('9%');
    }

    public function test_both_pages_are_linked_from_the_sidebar(): void
    {
        $this->actingAs($this->trader())
            ->get('/dashboard/certificates')
            ->assertOk()
            ->assertSee(route('dashboard.certificates'))
            ->assertSee(route('dashboard.guideline'))
            ->assertSee('Achievement')
            ->assertSee('Guideline');
    }
}
