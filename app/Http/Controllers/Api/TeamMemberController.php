<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamMemberRequest;
use App\Http\Requests\UpdateTeamMemberRequest;
use App\Http\Resources\TeamMemberResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TeamMemberController extends Controller
{
    public function index(Team $team): JsonResponse
    {
        Gate::authorize('view', $team);

        return response()->json([
            'data' => TeamMemberResource::collection($team->members()->with('user')->get()),
        ]);
    }

    public function store(StoreTeamMemberRequest $request, Team $team): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->firstOrFail();

        if ($team->hasMember($user)) {
            return response()->json(['message' => 'User is already a member'], 409);
        }

        $membership = $team->members()->create([
            'user_id' => $user->id,
            'role' => $request->input('role', 'member'),
            'joined_at' => now(),
        ]);

        return response()->json([
            'data' => new TeamMemberResource($membership->load('user')),
        ], 201);
    }

    public function update(UpdateTeamMemberRequest $request, Team $team, User $member): JsonResponse
    {
        Gate::authorize('updateMember', $team);

        if ($team->userIsOwner($member)) {
            return response()->json(['message' => 'You cannot modify the owner'], 403);
        }

        $membership = $team->members()->where('user_id', $member->id)->firstOrFail();

        $membership->update(['role' => $request->input('role')]);

        return response()->json([
            'data' => new TeamMemberResource($membership->fresh()->load('user')),
        ]);
    }

    public function destroy(Team $team, User $member): JsonResponse
    {
        Gate::authorize('removeMember', $team);

        if ($team->userIsOwner($member)) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $membership = $team->members()->where('user_id', $member->id)->firstOrFail();

        if (Auth::id() === $member->id) {
            $membership->delete();

            return response()->json(['message' => 'You left the team']);
        }

        $currentRole = $team->roleOf(Auth::id());

        if ($currentRole === 'admin' && $membership->role !== 'member') {
            return response()->json(['message' => 'An admin can only remove members'], 403);
        }

        $membership->delete();

        return response()->json(['message' => 'Member removed']);
    }
}
