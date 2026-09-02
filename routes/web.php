<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AchievementController;
use App\Http\Controllers\AffiliationController;
use App\Http\Controllers\GuidelineController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\WithdrawalController;
use App\Livewire\BuyChallenge;
use Illuminate\Support\Facades\Route;

/*
 * Public marketing website (Phase 09).
 */
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/pricing', [PublicController::class, 'pricing'])->name('pricing');
Route::get('/trading-rules', [PublicController::class, 'rules'])->name('rules');
Route::get('/about', [PublicController::class, 'about'])->name('about');
Route::get('/faq', [PublicController::class, 'faq'])->name('faq');
Route::get('/contact', [PublicController::class, 'contact'])->name('contact');
Route::post('/contact', [PublicController::class, 'contactSubmit'])->name('contact.submit');
Route::get('/legal/{doc}', [PublicController::class, 'legal'])->name('legal')
    ->where('doc', 'terms|privacy|refund');
// Convenience named routes for the footer.
Route::get('/legal/terms', [PublicController::class, 'legal'])->defaults('doc', 'terms')->name('legal.terms');
Route::get('/legal/privacy', [PublicController::class, 'legal'])->defaults('doc', 'privacy')->name('legal.privacy');
Route::get('/legal/refund', [PublicController::class, 'legal'])->defaults('doc', 'refund')->name('legal.refund');

// Referral capture: /r/CODE stores the code and sends the visitor to register.
Route::get('/r/{code}', [AffiliationController::class, 'referral'])->name('referral');

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

    // Affiliation / Rewards (Phase 07)
    Route::get('/dashboard/affiliation', [AffiliationController::class, 'index'])->name('dashboard.affiliation');
    Route::post('/dashboard/affiliation/share', [AffiliationController::class, 'share'])->name('dashboard.affiliation.share');
    Route::post('/dashboard/affiliation/reward', [AffiliationController::class, 'reward'])->name('dashboard.affiliation.reward');

    // Achievement (certificates + reward requests) & Guideline (Phase 10)
    Route::get('/dashboard/certificates', [AchievementController::class, 'index'])->name('dashboard.certificates');
    Route::post('/dashboard/certificates/reward', [AchievementController::class, 'requestReward'])->name('dashboard.certificates.reward');
    Route::get('/dashboard/guideline', [GuidelineController::class, 'index'])->name('dashboard.guideline');

    // Leaderboard, market widgets, downloads & notifications (Phase 08)
    Route::get('/dashboard/leaderboard', [LeaderboardController::class, 'index'])->name('dashboard.leaderboard');
    Route::view('/dashboard/heatmap', 'dashboard.heatmap')->name('dashboard.heatmap');
    Route::view('/dashboard/calendar', 'dashboard.calendar')->name('dashboard.calendar');
    Route::view('/dashboard/downloads', 'dashboard.downloads')->name('dashboard.downloads');

    Route::get('/dashboard/notifications', [NotificationController::class, 'index'])->name('dashboard.notifications');
    Route::post('/dashboard/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('dashboard.notifications.readAll');
    Route::post('/dashboard/notifications/{id}/read', [NotificationController::class, 'markRead'])->name('dashboard.notifications.read');
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
