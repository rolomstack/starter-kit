<?php

use App\Enums\BusinessRole;
use App\Models\Business;
use App\Models\User;
use App\Notifications\VerifyPhoneNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('the businesses index page can be rendered', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('businesses.index'));

    $response->assertOk();
});

test('businesses can be created', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('businesses.store'), [
            'name' => 'Test Business',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('businesses', [
        'name' => 'Test Business',
        'is_personal' => false,
    ]);
});

test('businesses can be created with custom email and phone', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('businesses.store'), [
            'name' => 'Test Business Custom',
            'email' => 'custom-business@example.com',
            'phone_country' => 'US',
            'phone' => '2015550123',
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('businesses', [
        'name' => 'Test Business Custom',
        'email' => 'custom-business@example.com',
        'phone' => '2015550123',
        'is_personal' => false,
    ]);
});

test('business creation fails with invalid phone or email', function () {
    $user = User::factory()->create();

    // Invalid email
    $response = $this
        ->actingAs($user)
        ->post(route('businesses.store'), [
            'name' => 'Invalid Email Business',
            'email' => 'not-an-email',
        ]);

    $response->assertSessionHasErrors(['email']);

    // Invalid phone number format for US
    $response = $this
        ->actingAs($user)
        ->post(route('businesses.store'), [
            'name' => 'Invalid Phone Business',
            'phone_country' => 'US',
            'phone' => '12345',
        ]);

    $response->assertSessionHasErrors(['phone']);
});

test('business slug uses next available suffix', function () {
    $user = User::factory()->create();

    Business::factory()->create(['name' => 'Acme', 'slug' => 'acme']);
    Business::factory()->create(['name' => 'Acme One', 'slug' => 'acme-1']);
    Business::factory()->create(['name' => 'Acme Ten', 'slug' => 'acme-10']);

    $this
        ->actingAs($user)
        ->post(route('businesses.store'), [
            'name' => 'Acme',
        ]);

    $this->assertDatabaseHas('businesses', [
        'name' => 'Acme',
        'slug' => 'acme-11',
    ]);
});

test('the business edit page can be rendered', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create([
        'email' => 'test@example.com',
        'phone' => '+2348031234567',
        'phone_verified_at' => now(),
    ]);

    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->get(route('businesses.edit', $business));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('businesses/edit')
            ->where('business.email', 'test@example.com')
            ->where('business.phone', '+2348031234567')
            ->where('business.phoneCountry', 'NG')
            ->has('countries')
            ->where('members.0.role', BusinessRole::Owner->value)
            ->where('members.0.role_label', BusinessRole::Owner->label()),
        );
});

test('businesses can be updated by owners', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create(['name' => 'Original Name']);

    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->patch(route('businesses.update', $business), [
            'name' => 'Updated Name',
            'email' => 'new-email@example.com',
        ]);

    $response->assertRedirect(route('businesses.edit', $business->fresh()));

    $this->assertDatabaseHas('businesses', [
        'id' => $business->id,
        'name' => 'Updated Name',
        'email' => 'new-email@example.com',
        'phone' => null,
    ]);
});

test('business update fails with invalid phone or email', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    // Invalid email
    $response = $this
        ->actingAs($user)
        ->patch(route('businesses.update', $business), [
            'name' => 'Name',
            'email' => 'not-an-email',
        ]);

    $response->assertSessionHasErrors(['email']);

    // Invalid phone number format for US
    $response = $this
        ->actingAs($user)
        ->patch(route('businesses.update', $business), [
            'name' => 'Name',
            'phone_country' => 'US',
            'phone' => '12345',
        ]);

    $response->assertSessionHasErrors(['phone']);
});

test('updating to a new phone number redirects to verification notice if not verified before', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create([
        'phone' => '2015550123',
        'phone_verified_at' => now(),
    ]);

    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    Notification::fake();

    $response = $this
        ->actingAs($user)
        ->patch(route('businesses.update', $business), [
            'name' => $business->name,
            'phone_country' => 'US',
            'phone' => '2015550124', // new number
        ]);

    $business = $business->fresh();
    expect($business->phone)->toEqual('2015550124');
    expect($business->hasVerifiedPhone())->toBeFalse();

    $response->assertRedirect(route('businesses.phone.notice', ['current_business' => $business->slug]));

    Notification::assertSentTo(
        $business,
        VerifyPhoneNotification::class
    );
});

test('updating to a new phone number auto-verifies if phone has been verified by user before', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create([
        'phone' => '2015550123',
        'phone_verified_at' => now(),
    ]);
    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $otherBusiness = Business::factory()->create([
        'phone' => '2015550124',
        'phone_verified_at' => now(), // verified
    ]);
    $otherBusiness->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    Notification::fake();

    $response = $this
        ->actingAs($user)
        ->patch(route('businesses.update', $business), [
            'name' => $business->name,
            'phone_country' => 'US',
            'phone' => '2015550124', // new number but verified in otherBusiness
        ]);

    $business = $business->fresh();
    expect($business->phone)->toEqual('2015550124');
    expect($business->hasVerifiedPhone())->toBeTrue();

    $response->assertRedirect(route('businesses.edit', ['business' => $business->slug]));

    Notification::assertNotSentTo(
        $business,
        VerifyPhoneNotification::class
    );
});

test('businesses cannot be updated by members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $response = $this
        ->actingAs($member)
        ->patch(route('businesses.update', $business), [
            'name' => 'Updated Name',
        ]);

    $response->assertForbidden();
});

test('businesses can be deleted by owners', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.destroy', $business), [
            'name' => $business->name,
        ]);

    $response->assertRedirect();

    $this->assertSoftDeleted('businesses', [
        'id' => $business->id,
    ]);
});

test('business deletion requires name confirmation', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.destroy', $business), [
            'name' => 'Wrong Name',
        ]);

    $response->assertSessionHasErrors('name');

    $this->assertDatabaseHas('businesses', [
        'id' => $business->id,
        'deleted_at' => null,
    ]);
});

test('deleting current business switches to alphabetically first remaining business', function () {
    $user = User::factory()->create(['name' => 'Mike']);

    $zuluBusiness = Business::factory()->create(['name' => 'Zulu Business']);
    $zuluBusiness->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $alphaBusiness = Business::factory()->create(['name' => 'Alpha Business']);
    $alphaBusiness->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $betaBusiness = Business::factory()->create(['name' => 'Beta Business']);
    $betaBusiness->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $user->update(['current_business_id' => $zuluBusiness->id]);

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.destroy', $zuluBusiness), [
            'name' => $zuluBusiness->name,
        ]);

    $response->assertRedirect();

    $this->assertSoftDeleted('businesses', [
        'id' => $zuluBusiness->id,
    ]);

    expect($user->fresh()->current_business_id)->toEqual($alphaBusiness->id);
});

test('deleting current business falls back to personal business when alphabetically first', function () {
    $user = User::factory()->create();
    $personalBusiness = $user->personalBusiness();
    $business = Business::factory()->create(['name' => 'Zulu Business']);
    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $user->update(['current_business_id' => $business->id]);

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.destroy', $business), [
            'name' => $business->name,
        ]);

    $response->assertRedirect();

    $this->assertSoftDeleted('businesses', [
        'id' => $business->id,
    ]);

    expect($user->fresh()->current_business_id)->toEqual($personalBusiness->id);
});

test('deleting non current business leaves current business unchanged', function () {
    $user = User::factory()->create();
    $personalBusiness = $user->personalBusiness();
    $business = Business::factory()->create();
    $business->members()->attach($user, ['role' => BusinessRole::Owner->value]);

    $user->update(['current_business_id' => $personalBusiness->id]);

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.destroy', $business), [
            'name' => $business->name,
        ]);

    $response->assertRedirect();

    $this->assertSoftDeleted('businesses', [
        'id' => $business->id,
    ]);

    expect($user->fresh()->current_business_id)->toEqual($personalBusiness->id);
});

test('members can leave non personal businesses', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $response = $this
        ->actingAs($member)
        ->delete(route('businesses.leave', $business));

    $response->assertRedirect(route('businesses.index'));
    $response->assertInertiaFlash('toast', ['type' => 'success', 'message' => "You left the business \"{$business->name}\""]);

    expect($member->fresh()->belongsToBusiness($business))->toBeFalse();
});

test('leaving current business switches to alphabetically first remaining business', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create(['name' => 'Mike']);

    $zuluBusiness = Business::factory()->create(['name' => 'Zulu Business']);
    $zuluBusiness->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $zuluBusiness->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $alphaBusiness = Business::factory()->create(['name' => 'Alpha Business']);
    $alphaBusiness->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $betaBusiness = Business::factory()->create(['name' => 'Beta Business']);
    $betaBusiness->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $member->update(['current_business_id' => $zuluBusiness->id]);

    $response = $this
        ->actingAs($member)
        ->delete(route('businesses.leave', $zuluBusiness));

    $response->assertRedirect(route('businesses.index'));

    expect($member->fresh()->belongsToBusiness($zuluBusiness))->toBeFalse();
    expect($member->fresh()->current_business_id)->toEqual($alphaBusiness->id);
});

test('personal businesses cannot be left', function () {
    $user = User::factory()->create();
    $personalBusiness = $user->personalBusiness();

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.leave', $personalBusiness));

    $response->assertForbidden();

    expect($user->fresh()->belongsToBusiness($personalBusiness))->toBeTrue();
});

test('business owners cannot leave their business', function () {
    $owner = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);

    $response = $this
        ->actingAs($owner)
        ->delete(route('businesses.leave', $business));

    $response->assertForbidden();

    expect($owner->fresh()->belongsToBusiness($business))->toBeTrue();
});

test('users cannot leave businesses they dont belong to', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.leave', $business));

    $response->assertForbidden();
});

test('deleting business switches other affected users to their personal business', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();

    $business = Business::factory()->create();
    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $owner->update(['current_business_id' => $business->id]);
    $member->update(['current_business_id' => $business->id]);

    $response = $this
        ->actingAs($owner)
        ->delete(route('businesses.destroy', $business), [
            'name' => $business->name,
        ]);

    $response->assertRedirect();

    expect($member->fresh()->current_business_id)->toEqual($member->personalBusiness()->id);
});

test('personal businesses cannot be deleted', function () {
    $user = User::factory()->create();

    $personalBusiness = $user->personalBusiness();

    $response = $this
        ->actingAs($user)
        ->delete(route('businesses.destroy', $personalBusiness), [
            'name' => $personalBusiness->name,
        ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('businesses', [
        'id' => $personalBusiness->id,
        'deleted_at' => null,
    ]);
});

test('businesses cannot be deleted by non owners', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($owner, ['role' => BusinessRole::Owner->value]);
    $business->members()->attach($member, ['role' => BusinessRole::Member->value]);

    $response = $this
        ->actingAs($member)
        ->delete(route('businesses.destroy', $business), [
            'name' => $business->name,
        ]);

    $response->assertForbidden();
});

test('users can switch businesses', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();

    $business->members()->attach($user, ['role' => BusinessRole::Member->value]);

    $response = $this
        ->actingAs($user)
        ->post(route('businesses.switch', $business));

    $response->assertRedirect();

    expect($user->fresh()->current_business_id)->toEqual($business->id);
});

test('users cannot switch to business they dont belong to', function () {
    $user = User::factory()->create();
    $business = Business::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('businesses.switch', $business));

    $response->assertForbidden();
});

test('guests cannot access businesses', function () {
    $response = $this->get(route('businesses.index'));

    $response->assertRedirect(route('login'));
});
