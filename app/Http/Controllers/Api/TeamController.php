<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeamMemberResource;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function index(): JsonResponse
    {
        $teams = Team::query()
            ->where('owner_id', Auth::id())
            ->orWhereHas('members', fn ($query) => $query->where('user_id', Auth::id()))
            ->get();

        return response()->json([
            'teams' => TeamResource::collection($teams),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team = Team::create([
            'name' => $data['name'],
            'slug' => $this->uniqueTeamSlug($data['name']),
            'owner_id' => Auth::id(),
        ]);

        $team->members()->create([
            'user_id' => Auth::id(),
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        return response()->json([
            'team' => new TeamResource($team),
        ], 201);
    }

    public function members(Team $team): JsonResponse
    {
        $this->authorizeTeamAccess($team);

        return response()->json([
            'members' => TeamMemberResource::collection($team->members()->with('user')->get()),
        ]);
    }

    public function addMember(Request $request, Team $team): JsonResponse
    {
        $this->authorizeTeamAccess($team);

        $currentRole = $team->roleOf(Auth::id());

        if (! in_array($currentRole, ['owner', 'admin'], true)) {
            return response()->json(['message' => 'You are not allowed to add members'], 403);
        }

        if ($currentRole === 'admin') {
            $data = $request->validate([
                'user_id' => ['required', 'exists:users,id'],
                'role' => ['required', 'in:member'],
            ]);
        } else {
            $data = $request->validate([
                'user_id' => ['required', 'exists:users,id'],
                'role' => ['required', 'in:member,admin'],
            ]);
        }

        if ($team->hasMember($data['user_id'])) {
            return response()->json([
                'message' => 'User is already a member',
            ], 409);
        }

        $membership = $team->members()->create([
            'user_id' => $data['user_id'],
            'role' => $data['role'],
            'joined_at' => now(),
        ]);

        return response()->json([
            'member' => new TeamMemberResource($membership->load('user')),
        ], 201);
    }

    public function updateMember(Request $request, Team $team, User $user): JsonResponse
    {
        $this->authorizeTeamAccess($team);

        if (! $team->userIsOwner(Auth::id())) {
            return response()->json(['message' => 'Only the owner can change roles'], 403);
        }

        if ($team->userIsOwner($user)) {
            return response()->json(['message' => 'You cannot modify the owner'], 403);
        }

        $membership = $team->members()->where('user_id', $user->id)->firstOrFail();

        $data = $request->validate([
            'role' => ['required', 'in:member,admin'],
        ]);

        $membership->update($data);

        return response()->json([
            'member' => new TeamMemberResource($membership->fresh()->load('user')),
        ]);
    }

    public function removeMember(Team $team, User $user): JsonResponse
    {
        $this->authorizeTeamAccess($team);

        $currentRole = $team->roleOf(Auth::id());
        if ($team->userIsOwner($user)) {
            return response()->json(['message' => 'You cannot remove the owner'], 403);
        }

        $membership = $team->members()->where('user_id', $user->id)->firstOrFail();

        if (Auth::id() === $user->id) {
            $membership->delete();

            return response()->json(['message' => 'You left the team']);
        }

        if (! in_array($currentRole, ['owner', 'admin'], true)) {
            return response()->json(['message' => 'You are not allowed to remove members'], 403);
        }

        if ($currentRole === 'admin' && $membership->role !== 'member') {
            return response()->json(['message' => 'An admin can only remove members'], 403);
        }

        $membership->delete();

        return response()->json(['message' => 'Member removed']);
    }

    private function authorizeTeamAccess(Team $team): void
    {
        if (! $team->hasMember(Auth::id())) {
            abort(403);
        }
    }

    private function uniqueTeamSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: Str::random(8);
        $slug = $baseSlug;
        $suffix = 2;

        while (Team::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
