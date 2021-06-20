<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Show all details and projects of specific user
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $user['projects'] = $user->projects()->whereHas('status', function ($query) {
            $query->where('name', 'ONGOING');
        })->with([
            'stacks',
            'status',
            'users',
            'type'
        ])->get();
        return response()->json([
            "message" => "Success!",
            "data" => [
                'user' => $user,
                'open_projects' => Project::whereHas('status', function ($query) {
                    $query->where('name', 'ONGOING');
                })->withCount('users')
                    ->with([
                        'stacks',
                        'status',
                        'type'
                    ])->get()->filter(function ($project) {
                        return $project->users_count < $project->max_member_count;
                    })
            ]
        ], 200);
    }

    public function filterOptions()
    {
        return response()->json([
            "message" => "Success!",
            "data" => [
                'user' => DB::table('users')->select('id', 'name')->get(),
                'stack' => DB::table('stacks')->get(),
                'status' => DB::table('statuses')->get(),
                'type' => DB::table('types')->get(),
            ]
        ], 200);
    }
}
