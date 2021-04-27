<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddStackRequest;
use Illuminate\Http\Request;
use App\Models\Stack;

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
        $data = $request->validated();
        $stack = new Stack;
        $stack->name = $data['name'];

        \DB::transaction(function () use ($stack) {
            $stack->save();
        });

        if ($stack->exists) {
            return response()->json([
                'message' => 'Stack added successfully!'
            ], 200);
        } else {
            return response()->json([
                'message' => 'Stack could not be created!'
            ], 503);
        }
    }
}
