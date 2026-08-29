<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

/*
 * Trader area — requires a verified, logged-in account.
 * The full dashboard (overview, orders, etc.) arrives in later phases;
 * for now this is a placeholder shell proving auth + roles work.
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
});

// The back office lives at /admin, owned by the Filament panel
// (see App\Providers\Filament\AdminPanelProvider). Access is gated to staff
// via User::canAccessPanel().
