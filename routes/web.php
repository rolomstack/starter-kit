<?php

use App\Http\Controllers\Businesses\BusinessInvitationController;
use App\Http\Controllers\Businesses\BusinessPhoneVerificationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Webhooks\PaystackWebhookController;
use App\Http\Middleware\EnsureBusinessMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::prefix('{business}')
    ->middleware(['auth', 'verified', 'verified.phone', EnsureBusinessMembership::class, 'subscribed'])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', [BusinessInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [BusinessInvitationController::class, 'decline'])->name('invitations.decline');
});

Route::prefix('{business}')
    ->middleware(['auth', EnsureBusinessMembership::class])
    ->group(function () {
        // Business Phone Verification Routes
        Route::get('phone/verify', [BusinessPhoneVerificationController::class, 'show'])->name('businesses.phone.notice');
        Route::post('phone/verify', [BusinessPhoneVerificationController::class, 'verify'])->name('businesses.phone.verify');
        Route::post('phone/verify/resend', [BusinessPhoneVerificationController::class, 'resend'])->name('businesses.phone.resend');
    });

Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])->name('webhooks.paystack');

require __DIR__.'/settings.php';
