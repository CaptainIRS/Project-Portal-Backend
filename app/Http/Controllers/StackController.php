<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddStackRequest;
use Illuminate\Http\Request;
use App\Models\Stack;
use Illuminate\Support\Facades\DB;

class StackController extends Controller
{
    /**
     * Get all stacks
     *
     * @return \Illuminate\Http\Response
     */
    public function all()
    {
        return response()->json([
            'message' => 'Success!',
            'data' => [
                'stacks' => Stack::all()
            ]
        ], 200);
    }

    /**
     * Adds new stack to the database
     *
     * @param  \App\Http\Requests\AddStackRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function add(AddStackRequest $request)
    {
        $stack = new Stack($request->only(['name']));

        DB::transaction(function () use ($stack) {
            $stack->save();
        });

        assert($stack->exists);
        return response()->json([
            'message' => 'Stack added successfully!'
        ], 200);
    }
}
