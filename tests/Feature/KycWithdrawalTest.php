<?php

namespace Tests\Feature;

use App\Filament\Resources\WithdrawalResource\Pages\ListWithdrawals;
use App\Models\KycDocument;
use App\Models\TradingAccount;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\Kyc\KycService;
use App\Services\Withdrawals\WithdrawalService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class KycWithdrawalTest extends TestCase
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

    private function fundedAccount(User $user, array $overrides = []): TradingAccount
    {
        return TradingAccount::create(array_merge([
            'user_id' => $user->id, 'platform' => 'mt5', 'account_size' => 10000,
            'challenge_type' => 'two_step', 'current_phase' => 2, 'status' => 'funded',
            'starting_balance' => 10000, 'current_equity' => 10800, 'trading_days_count' => 6,
        ], $overrides));
    }

    // ---- KYC ----------------------------------------------------------------

    public function test_kyc_is_locked_without_a_funded_account(): void
    {
        $trader = $this->trader();

        $this->actingAs($trader)->get('/dashboard/kyc')->assertOk()->assertSee('KYC is locked');

        Storage::fake('local');
        $this->actingAs($trader)->post('/dashboard/kyc', [
            'document_type' => 'passport',
            'document' => UploadedFile::fake()->create('p.jpg', 100, 'image/jpeg'),
        ])->assertForbidden();
    }

    public function test_funded_trader_can_upload_kyc(): void
    {
        Storage::fake('local');
        $trader = $this->trader();
        $this->fundedAccount($trader);

        $this->actingAs($trader)->post('/dashboard/kyc', [
            'document_type' => 'passport',
            'document' => UploadedFile::fake()->create('passport.jpg', 200, 'image/jpeg'),
        ])->assertRedirect();

        $doc = KycDocument::first();
        $this->assertNotNull($doc);
        $this->assertSame('pending', $doc->status);
        $this->assertSame('pending', $trader->fresh()->kyc_status);
        Storage::disk('local')->assertExists($doc->file_path);
    }

    public function test_admin_approve_and_reject_kyc(): void
    {
        Storage::fake('local');
        $trader = $this->trader();
        $this->fundedAccount($trader);
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');

        $doc = KycDocument::create([
            'user_id' => $trader->id, 'document_type' => 'passport',
            'file_path' => 'kyc/x.jpg', 'file_disk' => 'local', 'status' => 'pending',
        ]);

        $kyc = app(KycService::class);
        $kyc->approve($doc, $admin);
        $this->assertSame('approved', $doc->fresh()->status);
        $this->assertSame('approved', $trader->fresh()->kyc_status);

        $kyc->reject($doc->fresh(), $admin, 'Blurry scan');
        $this->assertSame('rejected', $doc->fresh()->status);
        $this->assertSame('Blurry scan', $doc->fresh()->remarks);
        $this->assertSame('rejected', $trader->fresh()->kyc_status);
    }

    public function test_kyc_download_is_staff_only(): void
    {
        $trader = $this->trader();
        $doc = KycDocument::create([
            'user_id' => $trader->id, 'document_type' => 'passport',
            'file_path' => 'kyc/x.jpg', 'file_disk' => 'local', 'status' => 'pending',
        ]);

        $this->actingAs($trader)->get(route('staff.kyc.download', $doc))->assertForbidden();
    }

    // ---- Withdrawals --------------------------------------------------------

    public function test_eligibility_requires_funded_kyc_and_profit(): void
    {
        $trader = $this->trader();
        $account = $this->fundedAccount($trader); // profit 800, but KYC not approved
        $service = app(WithdrawalService::class);

        $this->assertFalse($service->isEligible($account));

        $trader->forceFill(['kyc_status' => 'approved'])->save();
        $this->assertTrue($service->isEligible($account->fresh()));
        $this->assertSame(800.0, $service->availableProfit($account->fresh()));
    }

    public function test_trader_can_request_withdrawal_when_eligible(): void
    {
        $trader = $this->trader();
        $trader->forceFill(['kyc_status' => 'approved'])->save();
        $account = $this->fundedAccount($trader);

        $this->actingAs($trader)->post('/dashboard/withdrawal', [
            'trading_account_id' => $account->id,
            'amount' => 500,
            'method' => 'usdt_bsc',
            'wallet_address' => '0xWALLET',
        ])->assertRedirect();

        $w = Withdrawal::first();
        $this->assertSame('pending', $w->status);
        $this->assertEquals(500.0, (float) $w->amount);
        $this->assertSame('approved', $w->eligibility_snapshot['kyc_status']);
    }

    public function test_withdrawal_rejected_when_over_available_profit(): void
    {
        $trader = $this->trader();
        $trader->forceFill(['kyc_status' => 'approved'])->save();
        $account = $this->fundedAccount($trader); // available 800

        $this->actingAs($trader)->post('/dashboard/withdrawal', [
            'trading_account_id' => $account->id,
            'amount' => 5000,
            'method' => 'usdt_bsc',
            'wallet_address' => '0xWALLET',
        ])->assertSessionHasErrors('amount');

        $this->assertSame(0, Withdrawal::count());
    }

    public function test_admin_approves_and_marks_withdrawal_paid(): void
    {
        $trader = $this->trader();
        $admin = User::factory()->create(['email_verified_at' => now()]);
        $admin->assignRole('admin');
        $account = $this->fundedAccount($trader);

        $pending = Withdrawal::create([
            'withdrawal_number' => 'WD-TEST-1', 'user_id' => $trader->id, 'trading_account_id' => $account->id,
            'amount' => 500, 'method' => 'usdt_bsc', 'wallet_address' => '0xW', 'status' => 'pending',
        ]);
        Livewire::actingAs($admin)->test(ListWithdrawals::class)
            ->callTableAction('approve', $pending)->assertHasNoTableActionErrors();
        $this->assertSame('approved', $pending->fresh()->status);

        $approved = Withdrawal::create([
            'withdrawal_number' => 'WD-TEST-2', 'user_id' => $trader->id, 'trading_account_id' => $account->id,
            'amount' => 300, 'method' => 'usdt_bsc', 'wallet_address' => '0xW', 'status' => 'approved',
        ]);
        Livewire::actingAs($admin)->test(ListWithdrawals::class)
            ->callTableAction('markPaid', $approved, data: ['txid' => '0xTX'])->assertHasNoTableActionErrors();
        $this->assertSame('paid', $approved->fresh()->status);
        $this->assertSame('0xTX', $approved->fresh()->transaction_reference);
    }
}
