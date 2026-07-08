<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('registration screen includes team invitation context', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Laravel Team']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this->get(route('register', ['invitation' => $invitation->code]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/register')
        ->where('teamInvitation.code', $invitation->code)
        ->where('teamInvitation.teamName', 'Laravel Team'),
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

    $response->assertRedirect(route('teams.create'));
});

test('new users can register and join team immediately if invited', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create(['name' => 'Laravel Team']);
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
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
    expect($user->belongsToTeam($team))->toBeTrue();

    $response->assertRedirect(route('dashboard', ['current_team' => $team->slug]));
});

test('team creation fails with invalid phone country code during onboarding', function () {
    $user = User::factory()->create();
    $user->teams()->detach();
    $user->ownedTeams()->delete();
    $user->update(['current_team_id' => null]);

    $response = $this
        ->actingAs($user)
        ->post(route('teams.store'), [
            'name' => 'Acme Corp',
            'phone_country' => 'XX', // Invalid country
            'phone' => '555-555-5555',
        ]);

    $response->assertSessionHasErrors(['phone_country']);
});

test('team creation fails with incorrect phone number format for selected country during onboarding', function () {
    $user = User::factory()->create();
    $user->teams()->detach();
    $user->ownedTeams()->delete();
    $user->update(['current_team_id' => null]);

    $response = $this
        ->actingAs($user)
        ->post(route('teams.store'), [
            'name' => 'Acme Corp',
            'phone_country' => 'US',
            'phone' => '07123456789', // Invalid for US
        ]);

    $response->assertSessionHasErrors(['phone']);
});

test('users can complete setup business onboarding', function () {
    $user = User::factory()->create();
    $user->teams()->detach();
    $user->ownedTeams()->delete();
    $user->update(['current_team_id' => null]);

    $response = $this
        ->actingAs($user)
        ->post(route('teams.store'), [
            'name' => 'Acme Corp',
            'phone_country' => 'US',
            'phone' => '555-555-5555',
        ]);

    $team = Team::where('name', 'Acme Corp')->first();
    expect($team)->not->toBeNull();
    expect($team->is_personal)->toBeTrue();
    expect($team->phone)->toBe('555-555-5555');

    $response->assertRedirect(route('dashboard', ['current_team' => $team->slug]));
});
