<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListProjectsRequest;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Requests\UpdateProjectStatusRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    public function index(ListProjectsRequest $request, Team $team): AnonymousResourceCollection
    {
        Gate::authorize('view', $team);

        return ProjectResource::collection(
            $this->projectListingQuery($team, $request->validated())
                ->paginate($request->integer('per_page', 15))
                ->withQueryString()
        );
    }

    public function store(StoreProjectRequest $request, Team $team): JsonResponse
    {
        Gate::authorize('createForTeam', [Project::class, $team]);

        $project = $team->projects()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'status' => ProjectStatus::Active,
        ]);

        return (new ProjectResource($this->loadProjectRelations($project)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Project $project): ProjectResource
    {
        $project->loadMissing('team');
        Gate::authorize('view', $project);

        return new ProjectResource($this->loadProjectRelations($project));
    }

    public function update(UpdateProjectRequest $request, Project $project): ProjectResource
    {
        $project->loadMissing('team');
        Gate::authorize('update', $project);

        return new ProjectResource(
            $this->loadProjectRelations($this->applyProjectData($project, $request->validated()))
        );
    }

    public function updateStatus(UpdateProjectStatusRequest $request, Project $project): ProjectResource
    {
        $project->loadMissing('team');
        Gate::authorize('update', $project);

        return new ProjectResource(
            $this->loadProjectRelations($this->applyProjectData($project, $request->validated()))
        );
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->loadMissing('team');
        Gate::authorize('delete', $project);

        if ($project->tasks()->exists()) {
            return response()->json([
                'message' => 'Project has associated tasks and cannot be deleted.',
            ], Response::HTTP_CONFLICT);
        }

        $project->delete();

        return response()->json(['message' => 'Project deleted']);
    }

    /**
     * @param  array{status?: string, search?: string, sort?: string, direction?: string}  $filters
     */
    private function projectListingQuery(Team $team, array $filters): HasMany
    {
        $query = $team->projects()
            ->with(['team:id,name,slug', 'creator:id,name,email']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->where(function (Builder $query) use ($filters): void {
                $query->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }

        return $query->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc');
    }

    /**
     * @param  array{name?: string, description?: string|null, started_at?: string|null, status?: string}  $data
     */
    private function applyProjectData(Project $project, array $data): Project
    {
        $status = $data['status'] ?? null;
        unset($data['status']);

        $project->fill($data);

        if (is_string($status)) {
            $project->transitionTo(ProjectStatus::from($status));
        }

        $project->save();

        return $project->refresh();
    }

    private function loadProjectRelations(Project $project): Project
    {
        return $project->load(['team:id,name,slug', 'creator:id,name,email']);
    }
}
