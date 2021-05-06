<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stack;
use App\Models\Type;

class FilterController extends Controller
{
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
