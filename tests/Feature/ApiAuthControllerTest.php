<?php

use App\Models\User;

describe('API authentication', function () {
    it('registers a user and returns a structured user resource', function () {
        $response = $this->postJson('/api/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('user.email', 'jane@example.com');

        $this->assertAuthenticated();
    });

    it('logs in and returns the authenticated user resource', function () {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => bcrypt('Password123!'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
            ])
            ->assertJsonPath('user.email', $user->email);
    });

    it('logs out the authenticated user', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Logged out');

        $this->assertGuest();
    });

    it('returns the current authenticated user for me', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', $user->email);
    });
});
