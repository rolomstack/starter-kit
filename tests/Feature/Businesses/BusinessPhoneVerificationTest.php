<?php

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\Code;
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
        'purpose' => Code::PURPOSE_PHONE_VERIFICATION,
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
        'purpose' => Code::PURPOSE_PHONE_VERIFICATION,
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

test('business creation redirects to phone verification notice if phone is not verified before', function () {
    $user = User::factory()->create();

    Notification::fake();

    $response = $this->actingAs($user)->post(route('businesses.store'), [
        'name' => 'New Unverified Phone Business',
        'email' => 'new@example.com',
        'phone_country' => 'US',
        'phone' => '2015550123',
    ]);

    $business = Business::where('name', 'New Unverified Phone Business')->first();
    expect($business)->not->toBeNull();
    expect($business->hasVerifiedPhone())->toBeFalse();

    $response->assertRedirect(route('businesses.phone.notice', ['current_business' => $business->slug]));

    Notification::assertSentTo(
        $business,
        VerifyPhoneNotification::class
    );
});

test('business creation auto verifies and redirects normally if phone has been verified by user before', function () {
    $user = User::factory()->create();
    $personalBusiness = $user->personalBusiness();
    $personalBusiness->forceFill([
        'phone' => '2015550123',
        'phone_verified_at' => now(),
    ])->save();

    Notification::fake();

    $response = $this->actingAs($user)->post(route('businesses.store'), [
        'name' => 'New Auto Verified Business',
        'email' => 'new@example.com',
        'phone_country' => 'US',
        'phone' => '2015550123',
    ]);

    $business = Business::where('name', 'New Auto Verified Business')->first();
    expect($business)->not->toBeNull();
    expect($business->hasVerifiedPhone())->toBeTrue();

    $response->assertRedirect(route('businesses.edit', ['business' => $business->slug]));

    Notification::assertNotSentTo(
        $business,
        VerifyPhoneNotification::class
    );
});
