<?php

namespace App\Http\Controllers;

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
                ])->get()->makeHidden(['description', 'review', 'enddate', 'startdate'])
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function add(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|max:255',
            'description' => 'required',
            'startdate' => 'nullable|date|date_format:Y-m-d H:i:s|before:enddate',
            'enddate' => 'nullable|date|date_format:Y-m-d H:i:s',
            'max_member_count' => 'required|integer',
            'repo_link' => 'required|unique:projects,repo_link|url',
            'review' => 'nullable',
            'status' => 'required|exists:statuses,id',
            'type' => 'required|exists:types,id',
            'users' => 'nullable|array',
            'users.*.id' => 'required|exists:users,id',
            'users.*.role' => 'required|in:AUTHOR,MAINTAINER,DEVELOPER',
            'stacks' => 'required|array|min:1',
            'stacks.*' => 'exists:stacks,id'
        ]);

        $project = new Project;
        $project->name = $data['name'];
        $project->description = $data['description'];
        if (isset($data['enddate'])) {
            $project->enddate = $data['enddate'];
        }
        if (isset($data['startdate'])) {
          $project->startdate = $data['startdate'];
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $projectId
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $projectId)
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

        $data = $request->validate([
            'name' => 'required|max:255',
            'description' => 'required|max:5000',
            'startdate' => 'nullable|date|date_format:Y-m-d H:i:s|before:enddate',
            'enddate' => 'nullable|date|date_format:Y-m-d H:i:s',
            'max_member_count' => 'required|integer|min:1|max:100',
            'repo_link' => 'required|unique:projects,repo_link,' . $projectId,
            'review' => 'nullable|max:1000',
            'status' => 'required|exists:statuses,id',
            'type' => 'required|exists:types,id',
            'users' => 'nullable|array',
            'users.*.id' => 'required|exists:users,id',
            'users.*.role' => 'required|in:MAINTAINER,DEVELOPER',
            'stacks' => 'required|array|min:1',
            'stacks.*' => 'exists:stacks,id'
        ]);

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
        if (isset($data['enddate'])) {
            $project->enddate = $data['enddate'];
        }
        if (isset($data['startdate'])) {
          $project->startdate = $data['startdate'];
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
