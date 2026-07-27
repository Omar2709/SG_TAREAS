<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

describe('project module', function () {
    it('allows team members to list filtered and paginated projects', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);
        $team->members()->create(['user_id' => $member->id, 'role' => 'member', 'joined_at' => now()]);
        Project::factory()->create([
            'team_id' => $team->id,
            'created_by' => $owner->id,
            'name' => 'Zeta Project',
            'status' => 'active',
        ]);
        $completedProject = Project::factory()->create([
            'team_id' => $team->id,
            'created_by' => $owner->id,
            'name' => 'Alpha Project',
            'status' => 'completed',
        ]);

        $this->actingAs($member);

        $response = $this->getJson("/api/teams/{$team->id}/projects?status=completed&sort=name&direction=asc&per_page=1");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $completedProject->id)
            ->assertJsonPath('data.0.status', 'completed')
            ->assertJsonPath('meta.per_page', 1);
    });

    it('allows an owner to create projects', function () {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);

        $this->actingAs($owner);

        $response = $this->postJson("/api/teams/{$team->id}/projects", [
            'name' => 'New Project',
            'description' => 'Plan the rollout',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Project')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.team.id', $team->id)
            ->assertJsonPath('data.creator.id', $owner->id);

        $this->assertDatabaseHas('projects', [
            'team_id' => $team->id,
            'created_by' => $owner->id,
            'name' => 'New Project',
            'status' => 'active',
        ]);
    });

    it('prevents a member from creating a project', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);
        $team->members()->create(['user_id' => $member->id, 'role' => 'member', 'joined_at' => now()]);

        $this->actingAs($member);

        $response = $this->postJson("/api/teams/{$team->id}/projects", [
            'name' => 'Forbidden Project',
        ]);

        $response->assertForbidden()
            ->assertJsonPath('message', 'This action is unauthorized.');
    });

    it('allows an owner to complete and archive a project', function () {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);
        $project = Project::factory()->create(['team_id' => $team->id, 'created_by' => $owner->id]);

        $this->actingAs($owner);

        $completeResponse = $this->patchJson("/api/projects/{$project->id}", [
            'status' => 'completed',
        ]);

        $completeResponse->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $archiveResponse = $this->patchJson("/api/projects/{$project->id}", [
            'status' => 'archived',
        ]);

        $archiveResponse->assertOk()
            ->assertJsonPath('data.status', 'archived');

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'status' => 'archived',
        ]);
    });

    it('allows a team member to fetch a specific project', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);
        $team->members()->create(['user_id' => $member->id, 'role' => 'member', 'joined_at' => now()]);
        $project = Project::factory()->create(['team_id' => $team->id, 'created_by' => $owner->id]);

        $this->actingAs($member);

        $response = $this->getJson("/api/projects/{$project->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $project->id)
            ->assertJsonPath('data.team.id', $team->id)
            ->assertJsonPath('data.creator.id', $owner->id)
            ->assertJsonPath('data.creator.email', $owner->email)
            ->assertJsonMissingPath('data.creator.password')
            ->assertJsonMissingPath('data.creator.email_verified_at');
    });

    it('allows an owner to change a project status through the dedicated endpoint', function () {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);
        $project = Project::factory()->create(['team_id' => $team->id, 'created_by' => $owner->id]);

        $this->actingAs($owner);

        $response = $this->patchJson("/api/projects/{$project->id}/status", [
            'status' => 'completed',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'completed');
    });

    it('prevents a non-owner from deleting a project', function () {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);
        $team->members()->create(['user_id' => $member->id, 'role' => 'member', 'joined_at' => now()]);
        $project = Project::factory()->create(['team_id' => $team->id, 'created_by' => $owner->id]);

        $this->actingAs($member);

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertForbidden()
            ->assertJsonPath('message', 'This action is unauthorized.');
    });

    it('allows an owner to delete a project without tasks', function () {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);
        $project = Project::factory()->create(['team_id' => $team->id, 'created_by' => $owner->id]);

        $this->actingAs($owner);

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertOk()
            ->assertJsonPath('message', 'Project deleted');

        $this->assertModelMissing($project);
    });

    it('prevents deleting a project with associated tasks', function () {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'joined_at' => now()]);
        $project = Project::factory()->create(['team_id' => $team->id, 'created_by' => $owner->id]);

        Task::create([
            'project_id' => $project->id,
            'created_by' => $owner->id,
            'title' => 'Keep the project alive',
        ]);

        $this->actingAs($owner);

        $response = $this->deleteJson("/api/projects/{$project->id}");

        $response->assertConflict()
            ->assertJsonPath('message', 'Project has associated tasks and cannot be deleted.');

        $this->assertModelExists($project);
    });
});
