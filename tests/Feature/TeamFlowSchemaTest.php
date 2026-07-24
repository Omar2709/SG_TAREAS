<?php

use Illuminate\Support\Facades\Schema;

it('creates the teamflow database schema', function () {
    expect(Schema::hasColumns('teams', [
        'id',
        'name',
        'slug',
        'owner_id',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('team_members', [
        'id',
        'team_id',
        'user_id',
        'role',
        'joined_at',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('projects', [
        'id',
        'team_id',
        'created_by',
        'name',
        'description',
        'status',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('tasks', [
        'id',
        'project_id',
        'created_by',
        'assigned_to',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'completed_at',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('comments', [
        'id',
        'task_id',
        'user_id',
        'content',
        'created_at',
        'updated_at',
    ]))->toBeTrue();

    expect(Schema::hasColumns('activity_logs', [
        'id',
        'team_id',
        'user_id',
        'subject_type',
        'subject_id',
        'action',
        'description',
        'properties',
        'created_at',
        'updated_at',
    ]))->toBeTrue();
});
