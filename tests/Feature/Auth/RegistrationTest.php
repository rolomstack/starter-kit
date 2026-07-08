<?php

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\BusinessInvitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('registration screen includes business invitation context', function () {
    $owner = User::factory()->create();
    $business = Business::factory()->create(['name' => 'Laravel Business']);
    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $invitation = BusinessInvitation::factory()->create([
        'business_id' => $business->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this->get(route('register', ['invitation' => $invitation->code]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/register')
        ->where('businessInvitation.code', $invitation->code)
        ->where('businessInvitation.businessName', 'Laravel Business'),
    );
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $response->assertRedirect(route('businesses.create'));
});

test('new users can register and join business immediately if invited', function () {
    $owner = User::factory()->create();
    $business = Business::factory()->create(['name' => 'Laravel Business']);
    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $invitation = BusinessInvitation::factory()->create([
        'business_id' => $business->id,
        'email' => 'test@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this->post(route('register.store', ['invitation' => $invitation->code]), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    $user = User::where('email', 'test@example.com')->first();
    expect($user->belongsToBusiness($business))->toBeTrue();

    $response->assertRedirect(route('dashboard', ['current_business' => $business->slug]));
});

test('business creation fails with invalid phone country code during onboarding', function () {
    $user = User::factory()->create();
    $user->businesses()->detach();
    $user->ownedBusinesses()->delete();
    $user->update(['current_business_id' => null]);

    $response = $this
        ->actingAs($user)
        ->post(route('businesses.store'), [
            'name' => 'Acme Corp',
            'phone_country' => 'XX', // Invalid country
            'phone' => '555-555-5555',
        ]);

    $response->assertSessionHasErrors(['phone_country']);
});

test('business creation fails with incorrect phone number format for selected country during onboarding', function () {
    $user = User::factory()->create();
    $user->businesses()->detach();
    $user->ownedBusinesses()->delete();
    $user->update(['current_business_id' => null]);

    $response = $this
        ->actingAs($user)
        ->post(route('businesses.store'), [
            'name' => 'Acme Corp',
            'phone_country' => 'US',
            'phone' => '07123456789', // Invalid for US
        ]);

    $response->assertSessionHasErrors(['phone']);
});

test('users can complete setup business onboarding', function () {
    $user = User::factory()->create();
    $user->businesses()->detach();
    $user->ownedBusinesses()->delete();
    $user->update(['current_business_id' => null]);

    $response = $this
        ->actingAs($user)
        ->post(route('businesses.store'), [
            'name' => 'Acme Corp',
            'phone_country' => 'US',
            'phone' => '555-555-5555',
        ]);

    $business = Business::where('name', 'Acme Corp')->first();
    expect($business)->not->toBeNull();
    expect($business->is_personal)->toBeTrue();
    expect($business->phone)->toBe('555-555-5555');

    $response->assertRedirect(route('dashboard', ['current_business' => $business->slug]));
});
