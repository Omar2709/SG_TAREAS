<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;

it('allows an authenticated user to create a team, project and task', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $teamResponse = $this->post('/teams', [
        'name' => 'Platform Team',
        'description' => 'Delivery squad',
    ]);

    $teamResponse->assertRedirect();

    $team = Team::query()->where('name', 'Platform Team')->firstOrFail();

    expect($team->slug)->toBe('platform-team');
    expect($team->members()->whereKey($user->id)->exists())->toBeTrue();

    $projectResponse = $this->post("/teams/{$team->id}/projects", [
        'name' => 'Website refresh',
        'description' => 'Modernize the main experience',
    ]);

    $projectResponse->assertRedirect();

    $project = Project::query()->where('name', 'Website refresh')->firstOrFail();
    expect($project->team_id)->toBe($team->id);
    expect($project->created_by)->toBe($user->id);
    expect($project->status)->toBe('active');

    $taskResponse = $this->post("/teams/{$team->id}/projects/{$project->id}/tasks", [
        'title' => 'Create backlog',
        'description' => 'List the first set of tasks',
        'priority' => 'high',
    ]);

    $taskResponse->assertRedirect();

    $task = Task::query()->where('title', 'Create backlog')->firstOrFail();
    expect($task->project_id)->toBe($project->id);
    expect($task->created_by)->toBe($user->id);
    expect($task->status)->toBe('pending');
});
