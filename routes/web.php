<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\WithdrawalController;
use App\Livewire\BuyChallenge;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

/*
 * Trader area — requires a verified, logged-in account.
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        // Staff members are routed to the Filament back office (/admin) instead.
        if (auth()->user()->isStaff()) {
            return redirect('/admin');
        }

        return view('dashboard');
    })->name('dashboard');

    Route::view('/profile', 'profile')->name('profile');

    // Buy Challenge + Orders (Phase 03)
    Route::get('/dashboard/buynow', BuyChallenge::class)->name('dashboard.buynow');
    Route::get('/dashboard/orders', [OrderController::class, 'index'])->name('dashboard.orders');
    Route::get('/dashboard/orders/{order}/pay', [OrderController::class, 'pay'])->name('dashboard.orders.pay');

    // Account Overview (Phase 04)
    Route::get('/dashboard/accounts/{account}', [AccountController::class, 'show'])->name('dashboard.accounts.show');

    // KYC (Phase 06)
    Route::get('/dashboard/kyc', [KycController::class, 'show'])->name('dashboard.kyc');
    Route::post('/dashboard/kyc', [KycController::class, 'store'])->name('dashboard.kyc.store');

    // Withdrawals (Phase 06)
    Route::get('/dashboard/withdrawal', [WithdrawalController::class, 'index'])->name('dashboard.withdrawal');
    Route::post('/dashboard/withdrawal', [WithdrawalController::class, 'store'])->name('dashboard.withdrawal.store');
});

// Staff-only KYC file download (private — never a public URL).
Route::middleware(['auth', 'verified', 'staff'])
    ->get('/staff/kyc/{document}/download', [KycController::class, 'download'])
    ->name('staff.kyc.download');

/*
 * Payment gateway webhook (IPN). Public + CSRF-exempt (see bootstrap/app.php);
 * every request is signature-verified inside the gateway driver.
 */
Route::post('/webhooks/payment/{gateway}', [PaymentWebhookController::class, 'handle'])
    ->name('webhooks.payment');

// The back office lives at /admin, owned by the Filament panel
// (see App\Providers\Filament\AdminPanelProvider). Access is gated to staff
// via User::canAccessPanel().
