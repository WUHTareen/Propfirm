<?php

namespace Tests\Feature;

use App\Filament\Pages\PaymentSettings;
use App\Models\Setting;
use App\Models\User;
use App\Services\Payments\NowPaymentsGateway;
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

    public function test_api_key_is_stored_encrypted_and_blank_does_not_overwrite(): void
    {
        Livewire::actingAs($this->user('admin'))
            ->test(PaymentSettings::class)
            ->fillForm(['nowpayments_api_key' => 'super-secret-key'])
            ->call('save')
            ->assertHasNoFormErrors();

        // Stored value is encrypted (not the plaintext), but resolves back to it.
        $this->assertNotSame('super-secret-key', Setting::get('nowpayments_api_key'));
        $this->assertSame(
            'super-secret-key',
            NowPaymentsGateway::secret('nowpayments_api_key', 'payments.gateways.nowpayments.api_key'),
        );

        // Saving again with a blank field must NOT wipe the stored key.
        Livewire::actingAs($this->user('admin'))
            ->test(PaymentSettings::class)
            ->fillForm(['nowpayments_api_key' => ''])
            ->call('save');

        $this->assertSame(
            'super-secret-key',
            NowPaymentsGateway::secret('nowpayments_api_key', 'payments.gateways.nowpayments.api_key'),
        );
    }
}
