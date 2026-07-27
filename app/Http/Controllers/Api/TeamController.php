<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeamMemberResource;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Models\TeamMember;
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
        $this->authorizeMemberManagement($team);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'role' => ['required', 'in:member,admin'],
        ]);

        $member = $team->members()->where('user_id', $data['user_id'])->first();

        if ($member) {
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
        $this->authorizeMemberManagement($team);

        $membership = $team->members()->where('user_id', $user->id)->firstOrFail();

        if ($membership->role === 'owner') {
            return response()->json(['message' => 'You cannot modify the owner'], 403);
        }

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
        $this->authorizeMemberManagement($team);

        $membership = $team->members()->where('user_id', $user->id)->firstOrFail();

        if ($membership->role === 'owner') {
            return response()->json(['message' => 'You cannot remove the owner'], 403);
        }

        $membership->delete();

        return response()->json([
            'message' => 'Member removed',
        ]);
    }

    private function authorizeTeamAccess(Team $team): void
    {
        $isOwner = $team->owner_id === Auth::id();
        $isMember = $team->members()->where('user_id', Auth::id())->exists();

        if (! $isOwner && ! $isMember) {
            abort(403);
        }
    }

    private function authorizeMemberManagement(Team $team): void
    {
        $membership = $team->members()->where('user_id', Auth::id())->first();

        if (! $membership || ! in_array($membership->role, ['owner', 'admin'], true)) {
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
