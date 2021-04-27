<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Stack;
use App\Models\Type;

class FilterController extends Controller
{
    public function userFilter($user_id)
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

    public function stackFilter($stack_id)
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

    public function typeFilter($type_id)
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
