<?php

use App\Http\Controllers\Businesses\BusinessInvitationController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\EnsureBusinessMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{current_business}')
    ->middleware(['auth', 'verified', EnsureBusinessMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [BusinessInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [BusinessInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
