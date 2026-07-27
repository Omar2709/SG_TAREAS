<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id || $team->members()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id;
    }

    public function delete(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id;
    }

    public function addMember(User $user, Team $team): bool
    {
        $membership = $team->members()->where('user_id', $user->id)->first();

        return $membership?->role === 'owner' || $membership?->role === 'admin';
    }

    public function updateMember(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id;
    }

    public function removeMember(User $user, Team $team): bool
    {
        return $team->owner_id === $user->id || $team->members()->where('user_id', $user->id)->exists();
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
