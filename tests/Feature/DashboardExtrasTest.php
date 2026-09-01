<?php

namespace Tests\Feature;

use App\Models\TradingAccount;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Tests\TestCase;

class DashboardExtrasTest extends TestCase
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

    // ---- Leaderboard -------------------------------------------------------

    public function test_leaderboard_ranks_traders_by_profit(): void
    {
        $winner = $this->trader(['name' => 'Top Trader', 'country' => 'AE']);
        $runner = $this->trader(['name' => 'Second Trader', 'country' => 'GB']);

        $this->account($winner, ['current_equity' => 10800]); // +800
        $this->account($runner, ['current_equity' => 10300]); // +300

        $this->actingAs($winner)->get(route('dashboard.leaderboard'))
            ->assertOk()
            ->assertSee('Top Trader')
            ->assertSee('Second Trader')
            ->assertSeeInOrder(['Top Trader', 'Second Trader']);
    }

    public function test_leaderboard_filters_by_account_size(): void
    {
        $big = $this->trader(['name' => 'Big Size']);
        $small = $this->trader(['name' => 'Small Size']);

        $this->account($big, ['account_size' => 50000, 'starting_balance' => 50000, 'current_equity' => 51000]);
        $this->account($small, ['account_size' => 10000, 'current_equity' => 10500]);

        $this->actingAs($big)->get(route('dashboard.leaderboard', ['size' => 50000]))
            ->assertOk()
            ->assertSee('Big Size')
            ->assertDontSee('Small Size');
    }

    // ---- Notifications -----------------------------------------------------

    public function test_notifications_centre_lists_and_marks_read(): void
    {
        $trader = $this->trader();
        $trader->notify(new SimpleDashboardNotification('Account funded', 'Your account is now funded.'));

        $this->assertSame(1, $trader->unreadNotifications()->count());
        $id = $trader->notifications()->first()->id;

        $this->actingAs($trader)->get(route('dashboard.notifications'))
            ->assertOk()
            ->assertSee('Account funded');

        // Mark a single one read.
        $this->actingAs($trader)->post(route('dashboard.notifications.read', $id))->assertRedirect();
        $this->assertSame(0, $trader->unreadNotifications()->count());
    }

    public function test_mark_all_notifications_read(): void
    {
        $trader = $this->trader();
        $trader->notify(new SimpleDashboardNotification('One', 'first'));
        $trader->notify(new SimpleDashboardNotification('Two', 'second'));
        $this->assertSame(2, $trader->unreadNotifications()->count());

        $this->actingAs($trader)->post(route('dashboard.notifications.readAll'))->assertRedirect();
        $this->assertSame(0, $trader->fresh()->unreadNotifications()->count());
    }

    public function test_a_trader_cannot_mark_another_users_notification(): void
    {
        $owner = $this->trader();
        $other = $this->trader();
        $owner->notify(new SimpleDashboardNotification('Private', 'not yours'));
        $id = $owner->notifications()->first()->id;

        $this->actingAs($other)->post(route('dashboard.notifications.read', $id))->assertNotFound();
        $this->assertSame(1, $owner->unreadNotifications()->count());
    }

    // ---- Static widget & download pages ------------------------------------

    public function test_widget_and_download_pages_render(): void
    {
        $trader = $this->trader();

        $this->actingAs($trader)->get(route('dashboard.heatmap'))->assertOk()->assertSee('heatmap', false);
        $this->actingAs($trader)->get(route('dashboard.calendar'))->assertOk()->assertSee('calendar', false);
        $this->actingAs($trader)->get(route('dashboard.downloads'))->assertOk()
            ->assertSee('MetaTrader 5')
            ->assertSee('MetaTrader 4');
    }

    public function test_dashboard_pages_require_authentication(): void
    {
        $this->get(route('dashboard.leaderboard'))->assertRedirect(route('login'));
        $this->get(route('dashboard.notifications'))->assertRedirect(route('login'));
        $this->get(route('dashboard.downloads'))->assertRedirect(route('login'));
    }
}

/**
 * Minimal database notification used to exercise the notifications centre.
 */
class SimpleDashboardNotification extends Notification
{
    public function __construct(private string $title, private string $body)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->body,
        ];
    }
}
