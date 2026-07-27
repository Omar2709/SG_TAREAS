<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Team;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $project->team->hasMember($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Project $project): bool
    {
        if ($project->isArchived()) {
            return false;
        }

        return $project->team->userCanManage($user);
    }

    public function createForTeam(User $user, Team $team): bool
    {
        return $team->userCanManage($user);
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->team->userIsOwner($user);
    }

    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }
}
