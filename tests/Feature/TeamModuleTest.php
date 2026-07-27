<?php

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;

describe('team module', function () {
    it('allows an authenticated user to create a team', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/teams', [
            'name' => 'Acme Team',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('team.name', 'Acme Team');

        $this->assertDatabaseHas('teams', [
            'name' => 'Acme Team',
            'owner_id' => $user->id,
        ]);
    });

    it('lists the teams for the authenticated user', function () {
        $user = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $user->id]);
        $team->members()->create(['user_id' => $user->id, 'role' => 'owner', 'joined_at' => now()]);

        $this->actingAs($user);

        $response = $this->getJson('/api/teams');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => $team->name]);
    });

    it('lists the members of a team', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);
        $team->members()->create(['user_id' => $member->id, 'role' => 'member', 'joined_at' => now()]);

        $this->actingAs($owner);

        $response = $this->getJson("/api/teams/{$team->id}/members");

        $response->assertStatus(200)
            ->assertJsonFragment(['email' => $member->email]);
    });

    it('allows the owner to add a member', function () {
        $owner = User::factory()->create();
        $newMember = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);

        $this->actingAs($owner);

        $response = $this->postJson("/api/teams/{$team->id}/members", [
            'email' => $newMember->email,
            'role' => 'member',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['email' => $newMember->email]);

        $this->assertDatabaseHas('team_members', [
            'team_id' => $team->id,
            'user_id' => $newMember->id,
            'role' => 'member',
        ]);
    });

    it('prevents a regular member from adding other members', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $newMember = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);
        $team->members()->create(['user_id' => $member->id, 'role' => 'member', 'joined_at' => now()]);

        $this->actingAs($member);

        $response = $this->postJson("/api/teams/{$team->id}/members", [
            'email' => $newMember->email,
            'role' => 'member',
        ]);

        $response->assertStatus(403);
    });

    it('allows an admin to add only member-role users', function () {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $newMember = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);
        $team->members()->create(['user_id' => $admin->id, 'role' => 'admin', 'joined_at' => now()]);

        $this->actingAs($admin);

        $response = $this->postJson("/api/teams/{$team->id}/members", [
            'email' => $newMember->email,
            'role' => 'member',
        ]);

        $response->assertStatus(201);
    });

    it('prevents an admin from adding an admin', function () {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $newAdmin = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);
        $team->members()->create(['user_id' => $admin->id, 'role' => 'admin', 'joined_at' => now()]);

        $this->actingAs($admin);

        $response = $this->postJson("/api/teams/{$team->id}/members", [
            'email' => $newAdmin->email,
            'role' => 'admin',
        ]);

        $response->assertStatus(422);
    });

    it('prevents a non-owner from changing roles', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);
        $team->members()->create(['user_id' => $member->id, 'role' => 'member', 'joined_at' => now()]);

        $this->actingAs($member);

        $response = $this->patchJson("/api/teams/{$team->id}/members/{$member->id}", [
            'role' => 'admin',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'This action is unauthorized.');
    });

    it('prevents deleting the owner', function () {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);

        $this->actingAs($owner);

        $response = $this->deleteJson("/api/teams/{$team->id}/members/{$owner->id}");

        $response->assertStatus(403)
            ->assertJsonPath('message', 'This action is unauthorized.');
    });

    it('allows a user to leave the team', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);
        $team->members()->create(['user_id' => $member->id, 'role' => 'member', 'joined_at' => now()]);

        $this->actingAs($member);

        $response = $this->deleteJson("/api/teams/{$team->id}/members/{$member->id}");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'You left the team');

        $this->assertDatabaseMissing('team_members', [
            'team_id' => $team->id,
            'user_id' => $member->id,
        ]);
    });
});
