<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(): Response
    {
        $teams = Team::query()->withCount('projects')->with('owner')->get();

        return Inertia::render('teams/index', [
            'teams' => $teams,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $team = Team::create([
            'name' => $data['name'],
            'slug' => $this->uniqueTeamSlug($data['name']),
            'owner_id' => $request->user()->id,
        ]);

        $team->members()->attach($request->user()->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        return redirect()->route('teams.index');
    }

    public function storeProject(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $team->projects()->create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('teams.index');
    }

    public function storeTask(Request $request, Team $team, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'in:low,medium,high,urgent'],
        ]);

        $project->tasks()->create([
            ...$data,
            'created_by' => $request->user()->id,
            'status' => 'pending',
        ]);

        return redirect()->route('teams.index');
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
