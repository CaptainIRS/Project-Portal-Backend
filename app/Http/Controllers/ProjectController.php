<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddProjectRequest;
use App\Http\Requests\EditProjectRequest;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\User;
use App\Models\Stack;
use App\Models\Type;

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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $projectId
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
        $data = $request->validated();

        $project = new Project;
        $project->name = $data['name'];
        $project->description = $data['description'];
        if (isset($data['deadline'])) {
            $project->deadline = $data['deadline'];
        }
        if (isset($data['review'])) {
            $project->review = $data['review'];
        }
        $project->max_member_count = $data['max_member_count'];
        $project->repo_link = $data['repo_link'];

        \DB::transaction(function () use ($project, $user, $data) {

            // Associate one-to-one relations
            $project->type()->associate($data['type']);
            $project->status()->associate($data['status']);
            $project->save();

            // Save many-to-many relations
            $project->users()->syncWithoutDetaching([
                $user->id =>
                ['role' => 'AUTHOR']
            ]);
            if (isset($data['users'])) {
                foreach ($data['users'] as $projectUser) {
                    $project->users()->syncWithoutDetaching([
                        (int) $projectUser['id'] =>
                        ['role' => $projectUser['role']]
                    ]);
                }
            }
            $project->stacks()->syncWithoutDetaching($data['stacks']);
            $project->save();
        });

        if ($project->exists) {
            return response()->json([
                'message' => 'Project created successfully!',
            ], 200);
        } else {
            return response()->json([
                'message' => 'Project could not be created!'
            ], 503);
        }
    }

    /**
     * Edit the specified project in storage.
     *
     * @param  \App\Http\Requests\EditProjectRequest  $request
     * @param  int  $projectId
     * @return \Illuminate\Http\Response
     */
    public function edit(EditProjectRequest $request, $projectId)
    {
        $user = $request->user();
        try {
            $project = Project::findOrFail($projectId);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Project doesn\'t exist!'
            ], 404);
        }

        if (!$project->users()
            ->where(function ($query) {
                $query->where('project_user.role', 'AUTHOR')
                    ->orWhere('project_user.role', 'MAINTAINER');
            })->get()
            ->contains($user->id)) {
            return response()->json([
                'message' => 'You are not allowed to edit this project!'
            ], 403);
        }

        $data = $request->validated();

        if (isset($data['users'])) {
            if (count($data['users']) + $project->users()->wherePivot('role', 'AUTHOR')->count() > $data['max_member_count']) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => [
                        'max_member_count' => 'Max member count is less than current user count'
                    ]
                ], 422);
            }
            $projectAuthors = $project->users()->wherePivot('role', 'AUTHOR')->get();
            foreach ($data['users'] as $user) {
                if ($projectAuthors->contains($user['id'])) {
                    return response()->json([
                        'message' => 'The given data was invalid.',
                        'errors' => [
                            'users' => 'Author cannot take any other role'
                        ]
                    ], 422);
                }
            }
        }

        $project->name = $data['name'];
        $project->description = $data['description'];
        if (isset($data['deadline'])) {
            $project->deadline = $data['deadline'];
        }
        if (isset($data['review'])) {
            $project->review = $data['review'];
        }
        $project->max_member_count = $data['max_member_count'];
        $project->repo_link = $data['repo_link'];

        \DB::transaction(function () use ($project, $user, $data) {

            // Associate one-to-one relations
            $project->type()->associate($data['type']);
            $project->status()->associate($data['status']);
            $project->save();

            if (isset($data['users'])) {
                $authors = $project->users()->wherePivot('role', 'AUTHOR')->get();
                $project->users()->sync([]);
                foreach ($authors as $author) {
                    $project->users()->attach([
                        $author->id =>
                        ['role' => 'AUTHOR']
                    ]);
                }
                foreach ($data['users'] as $projectUser) {
                    $project->users()->syncWithoutDetaching([
                        $projectUser['id'] =>
                        ['role' => $projectUser['role']]
                    ]);
                }
            }
            $project->stacks()->sync([]);
            $project->stacks()->syncWithoutDetaching($data['stacks']);
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
    public function delete(Request $request, $projectId)
    {
        $user = $request->user();
        try {
            $project = Project::findOrFail($projectId);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Project doesn\'t exist!'
            ], 404);
        }

        if (!$project->users()
            ->wherePivot('role', 'AUTHOR')
            ->get()
            ->contains($user->id)) {
            return response()->json([
                'message' => 'You are not allowed to delete this project!'
            ], 403);
        }

        \DB::transaction(function () use ($project, $projectId) {

            $project->users()->each(function ($user) use ($projectId) {
                $user->projects()->syncWithoutDetaching([
                    $projectId =>
                    ['deleted_at' => \DB::raw('CURRENT_TIMESTAMP')]
                ]);
            });
            $project->stacks()->each(function ($stack) use ($projectId) {
                $stack->projects()->syncWithoutDetaching([
                    $projectId =>
                    ['deleted_at' => \DB::raw('CURRENT_TIMESTAMP')]
                ]);
            });

            $project->delete();
        });

        if ($project->trashed()) {
            return response()->json([
                'message' => 'Project deleted successfully!'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Project could not be deleted!'
            ], 503);
        }
    }

    public function user_filter($user_id)
    {
        $filter = User::where('id', $user_id)->with(
         'projects.stacks',
         'projects.type',
         'projects.status')->get('id');

        return response()->json([
            'message' => 'Success!',
            'data' => [
                'filtered' => $filter
            ]
        ], 200);
    }

    public function stack_filter($stack_id)
    {
        $filter = Stack::where('id', $stack_id)->with(
         'projects.stacks',
         'projects.type',
         'projects.status')->get('id');

        return response()->json([
            'message' => 'Success!',
            'data' => [
                'filtered' => $filter
            ]
        ], 200);
    }

    public function type_filter($type_id)
    {
        $filter = Type::where('id', $type_id)->with(
         'projects.stacks',
         'projects.type',
         'projects.status')->get('id');

        return response()->json([
            'message' => 'Success!',
            'data' => [
                'filtered' => $filter
            ]
        ], 200);
    }
}
