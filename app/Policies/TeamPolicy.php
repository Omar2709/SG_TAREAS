<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Team $team): bool
    {
        return $team->hasMember($user);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Team $team): bool
    {
        return $team->userIsOwner($user);
    }

    public function delete(User $user, Team $team): bool
    {
        return $team->userIsOwner($user);
    }

    public function addMember(User $user, Team $team): bool
    {
        return $team->userCanManage($user);
    }

    public function createProject(User $user, Team $team): bool
    {
        return $team->userCanManage($user);
    }

    public function updateMember(User $user, Team $team): bool
    {
        return $team->userIsOwner($user);
    }

    public function removeMember(User $user, Team $team): bool
    {
        return $team->hasMember($user);
    }

    public function restore(User $user, Team $team): bool
    {
        return false;
    }

    public function forceDelete(User $user, Team $team): bool
    {
        return false;
    }
}
