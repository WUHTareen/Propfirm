<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_login_and_register_screens_render(): void
    {
        $this->get('/login')->assertOk()->assertSee('Welcome back');
        $this->get('/register')->assertOk()->assertSee('Create your account');
    }

    public function test_registration_creates_a_trader_with_referral_code(): void
    {
        $this->post('/register', [
            'name' => 'Jane Trader',
            'email' => 'jane@example.com',
            'password' => 'password1234',
            'password_confirmation' => 'password1234',
        ]);

        $user = User::where('email', 'jane@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('trader'));
        $this->assertNotEmpty($user->referral_code);
    }

    public function test_guests_are_redirected_from_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_verified_trader_can_view_the_dashboard(): void
    {
        $trader = User::factory()->create(['email_verified_at' => now()]);
        $trader->assignRole('trader');

        $this->actingAs($trader)->get('/dashboard')->assertOk()->assertSee('No accounts yet');
    }

    public function test_staff_are_redirected_to_the_back_office(): void
    {
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('admin.home'));
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Back office');
    }

    public function test_traders_cannot_reach_the_back_office(): void
    {
        $trader = User::factory()->create(['email_verified_at' => now()]);
        $trader->assignRole('trader');

        $this->actingAs($trader)->get('/admin')->assertForbidden();
    }

    public function test_guest_password_screens_render(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee('Forgot your password');
        $this->get('/reset-password/faketoken')->assertOk()->assertSee('Set a new password');
    }

    public function test_profile_page_renders_for_a_logged_in_user(): void
    {
        $trader = User::factory()->create(['email_verified_at' => now()]);
        $trader->assignRole('trader');

        $this->actingAs($trader)->get('/profile')
            ->assertOk()
            ->assertSee('Profile &amp; security', false)
            ->assertSee('Two-factor authentication');
    }

    public function test_verify_email_notice_renders_for_unverified_user(): void
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $user->assignRole('trader');

        $this->actingAs($user)->get('/email/verify')->assertOk()->assertSee('Verify your email');
    }
}
