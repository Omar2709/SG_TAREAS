<?php

use App\Models\User;

it('registers a user and returns the authenticated profile', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('user.email', 'jane@example.com')
        ->assertJsonPath('user.name', 'Jane Doe');

    $this->assertAuthenticatedAs(User::where('email', 'jane@example.com')->first());
});

it('logs in an existing user and returns the current user', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => bcrypt('Password123!'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'login@example.com',
        'password' => 'Password123!',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('user.email', 'login@example.com');

    $this->assertAuthenticatedAs($user);
});

it('logs out an authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->postJson('/api/logout');

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Logged out');

    $this->assertGuest();
});

it('returns the authenticated user for the me endpoint', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->getJson('/api/me');

    $response->assertStatus(200)
        ->assertJsonPath('user.email', $user->email);
});
