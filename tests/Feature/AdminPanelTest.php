<?php

namespace Tests\Feature;

use App\Filament\Resources\ChallengePlanResource\Pages\CreateChallengePlan;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelTest extends TestCase
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

    public function test_admin_can_open_the_panel_and_plan_builder(): void
    {
        $admin = $this->staff('admin');

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/challenge-plans')->assertOk();
        $this->actingAs($admin)->get('/admin/challenge-plans/create')->assertOk();
    }

    public function test_traders_cannot_access_the_panel(): void
    {
        $trader = $this->staff('trader');

        $this->actingAs($trader)->get('/admin')->assertForbidden();
    }

    public function test_support_staff_reach_the_panel_but_not_the_plan_builder(): void
    {
        // Support is staff (panel access) but lacks 'manage challenge plans'.
        $support = $this->staff('support');

        $this->actingAs($support)->get('/admin')->assertOk();
        $this->actingAs($support)->get('/admin/challenge-plans')->assertForbidden();
    }

    public function test_guests_are_redirected_to_the_panel_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_can_create_a_plan_through_the_builder(): void
    {
        $admin = $this->staff('admin');

        Livewire::actingAs($admin)
            ->test(CreateChallengePlan::class)
            ->fillForm([
                'name' => '2-Step $10,000',
                'slug' => 'two-step-10000-test',
                'challenge_type' => 'two_step',
                'account_size' => 10000,
                'price' => 99,
                'currency' => 'USD',
                'profit_split_percent' => 80,
                'daily_drawdown_percent' => 5,
                'max_drawdown_percent' => 10,
                'drawdown_type' => 'static',
                'leverage' => 100,
                'min_trading_days' => 4,
                'phases' => [
                    ['phase' => 1, 'profit_target_percent' => 7, 'min_trading_days' => 4],
                    ['phase' => 2, 'profit_target_percent' => 5, 'min_trading_days' => 4],
                ],
                'is_active' => true,
                'sort_order' => 0,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('challenge_plans', [
            'slug' => 'two-step-10000-test',
            'challenge_type' => 'two_step',
            'account_size' => 10000,
        ]);
    }
}
