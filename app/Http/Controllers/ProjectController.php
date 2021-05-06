<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddProjectRequest;
use App\Http\Requests\EditProjectRequest;
use App\Http\Resources\ProjectResource;
use Illuminate\Http\Request;
use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    /**
     * Get all projects
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        return response()->json([
            'message' => 'Success!',
            'data' => [
                'projects' => Project::with([
                    'stacks',
                    'status',
                    'type'
                ])->get()->makeHidden(['description', 'review', 'deadline'])
            ]
        ], 200);
    }

    /**
     * Show project of specific id.
     *
     * @param  \App\Models\Project  $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $projectId)
    {
        $userId = $request->user()->id;

        try {
            Project::findOrFail($projectId);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Project doesn\'t exist!'
            ], 404);
        }
        return response()->json([
            'message' => 'Success!',
            'data' => [
                'project' => Project::where('id', $projectId)->with([
                    'feedbacks' => function ($feedback) use ($userId) {
                        $feedback->where('sender_id', $userId)
                            ->orWhere('receiver_id', $userId);
                    },
                    'stacks',
                    'status',
                    'type'
                ])->get()->each(function ($project) {
                    $project['users'] = [
                        'authors' => $project->users()->wherePivot('role', 'AUTHOR')->get(),
                        'maintainers' => $project->users()->wherePivot('role', 'MAINTAINER')->get(),
                        'developers' => $project->users()->wherePivot('role', 'DEVELOPER')->get()
                    ];
                })
            ]
        ], 200);
    }

    /**
     * Store new project.
     *
     * @param  \App\Http\Requests\AddProjectRequest $request
     * @return \Illuminate\Http\Response
     */
    public function add(AddProjectRequest $request)
    {
        $user = $request->user();
        $project = new Project($request->only([
            'name', 'description', 'deadline', 'review', 'max_member_count', 'repo_link'
        ]));

        DB::transaction(function () use ($project, $user, $request) {

            // Associate one-to-one relations
            $project->type()->associate($request->type);
            $project->status()->associate($request->status);
            $project->save();

            // Save many-to-many relations
            $project->users()->syncWithoutDetaching([
                $user->id =>
                ['role' => 'AUTHOR']
            ]);
            if (isset($request->users)) {
                foreach ($request->users as $projectUser) {
                    $project->users()->syncWithoutDetaching([
                        (int) $projectUser['id'] =>
                        ['role' => $projectUser['role']]
                    ]);
                }
            }
            $project->stacks()->syncWithoutDetaching($request->stacks);
            $project->save();
        });

        assert($project->exists);
        return response()->json([
            'message' => 'Project created successfully!',
        ], 200);
    }

    /**
     * Edit the specified project in storage.
     *
     * @param  \App\Http\Requests\EditProjectRequest  $request
     * @param  int  $projectId
     * @return \Illuminate\Http\Response
     */
    public function edit(EditProjectRequest $request, Project $project)
    {
        $project->update($request->only([
            'name', 'description', 'deadline', 'review', 'max_member_count', 'repo_link'
        ]));

        DB::transaction(function () use ($project, $request) {

            // Associate one-to-one relations
            $project->type()->associate($request->type);
            $project->status()->associate($request->status);
            $project->save();

            if (isset($request->users)) {
                $authors = $project->users()->wherePivot('role', 'AUTHOR')->get();
                $project->users()->sync([]);
                foreach ($authors as $author) {
                    $project->users()->attach([
                        $author->id =>
                        ['role' => 'AUTHOR']
                    ]);
                }
                foreach ($request->users as $projectUser) {
                    $project->users()->syncWithoutDetaching([
                        $projectUser['id'] =>
                        ['role' => $projectUser['role']]
                    ]);
                }
            }
            $project->stacks()->sync([]);
            $project->stacks()->syncWithoutDetaching($request->stacks);
            $project->save();
        });

        return response()->json([
            'message' => 'Project edited successfully!'
        ], 200);
    }

    /**
     * Remove the specified project from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $projectId
     * @return \Illuminate\Http\Response
     */
    public function delete(Project $project)
    {
        $this->authorize('delete', $project);

        DB::transaction(function () use ($project) {

            $project->users()->each(function ($user) use ($project) {
                $user->projects()->syncWithoutDetaching([
                    $project->id =>
                    ['deleted_at' => DB::raw('CURRENT_TIMESTAMP')]
                ]);
            });
            $project->stacks()->each(function ($stack) use ($project) {
                $stack->projects()->syncWithoutDetaching([
                    $project->id =>
                    ['deleted_at' => DB::raw('CURRENT_TIMESTAMP')]
                ]);
            });

            $project->delete();
        });

        assert($project->trashed());
        return response()->json([
            'message' => 'Project deleted successfully!'
        ], 200);
    }
}
