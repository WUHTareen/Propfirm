<?php

namespace Tests\Feature;

use App\Filament\Pages\PaymentSettings;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function user(string $role): User
    {
        $u = User::factory()->create(['email_verified_at' => now()]);
        $u->assignRole($role);

        return $u;
    }

    public function test_only_admins_can_open_payment_settings(): void
    {
        $this->actingAs($this->user('admin'))->get('/admin/payment-settings')->assertOk();
        $this->actingAs($this->user('support'))->get('/admin/payment-settings')->assertForbidden();
    }

    public function test_admin_can_save_gateway_and_wallets(): void
    {
        Livewire::actingAs($this->user('admin'))
            ->test(PaymentSettings::class)
            ->fillForm([
                'payment_gateway' => 'nowpayments',
                'wallet_usdt_bsc' => '0xBSCWALLET',
                'wallet_btc' => 'bc1qexample',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('nowpayments', Setting::get('payment_gateway'));
        $this->assertSame('0xBSCWALLET', Setting::get('wallet_usdt_bsc'));
        $this->assertSame('bc1qexample', Setting::get('wallet_btc'));
    }
}
