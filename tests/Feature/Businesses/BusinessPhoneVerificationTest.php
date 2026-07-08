<?php

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\User;
use App\Notifications\VerifyPhoneNotification;
use Illuminate\Support\Facades\Notification;

test('business phone verification screen can be rendered', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create([
        'phone' => '1234567890',
        'phone_verified_at' => null,
    ]);
    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);
    $user->switchBusiness($business);

    $response = $this->actingAs($user)->get(route('businesses.phone.notice', ['current_business' => $business->slug]));

    $response->assertOk();
});

test('unverified business phone redirects to verification notice', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create([
        'phone' => '1234567890',
        'phone_verified_at' => null,
    ]);
    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);
    $user->switchBusiness($business);

    $response = $this->actingAs($user)->get(route('dashboard', ['current_business' => $business->slug]));

    $response->assertRedirect(route('businesses.phone.notice', ['current_business' => $business->slug]));
});

test('business phone can be verified with correct token', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create([
        'phone' => '1234567890',
        'phone_verified_at' => null,
        'email' => 'business@example.com',
    ]);
    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);
    $user->switchBusiness($business);

    $token = '123456';
    $business->codes()->create([
        'purpose' => 'phone_verification',
        'recipient' => $business->phone,
        'code' => $token,
        'expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->actingAs($user)->post(route('businesses.phone.verify', ['current_business' => $business->slug]), [
        'code' => $token,
    ]);

    expect($business->fresh()->hasVerifiedPhone())->toBeTrue();
    $response->assertRedirect(route('dashboard', ['current_business' => $business->slug]));
});

test('business phone verification fails with incorrect token', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create([
        'phone' => '1234567890',
        'phone_verified_at' => null,
    ]);
    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);
    $user->switchBusiness($business);

    $token = '123456';
    $business->codes()->create([
        'purpose' => 'phone_verification',
        'recipient' => $business->phone,
        'code' => $token,
        'expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->actingAs($user)->post(route('businesses.phone.verify', ['current_business' => $business->slug]), [
        'code' => '654321', // wrong code
    ]);

    expect($business->fresh()->hasVerifiedPhone())->toBeFalse();
    $response->assertSessionHasErrors('code');
});

test('business phone verification code can be resent', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create([
        'phone' => '1234567890',
        'phone_verified_at' => null,
        'email' => 'business@example.com',
    ]);
    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);
    $user->switchBusiness($business);

    Notification::fake();

    $response = $this->actingAs($user)->post(route('businesses.phone.resend', ['current_business' => $business->slug]));

    $response->assertRedirect();
    $response->assertSessionHas('status', 'code-sent');

    Notification::assertSentTo(
        $business,
        VerifyPhoneNotification::class
    );
});
