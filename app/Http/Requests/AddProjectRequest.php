<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;

class AddProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', Project::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|max:255',
            'description' => 'required',
            'deadline' => 'nullable|date|date_format:Y-m-d H:i:s|after_or_equal:today',
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
        ];
    }
}
