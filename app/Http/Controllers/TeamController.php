<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ...$data,
            'owner_id' => $request->user()->id,
        ]);

        $team->members()->attach($request->user()->id, ['role' => 'owner']);

        return redirect()->route('teams.index');
    }

    public function storeProject(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $team->projects()->create($data);

        return redirect()->route('teams.index');
    }

    public function storeTask(Request $request, Team $team, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['nullable', 'in:low,medium,high'],
        ]);

        $project->tasks()->create([
            ...$data,
            'status' => 'pending',
        ]);

        return redirect()->route('teams.index');
    }
}
