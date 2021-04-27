<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
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
            'description' => 'required|max:5000',
            'deadline' => 'nullable|date|date_format:Y-m-d H:i:s|after_or_equal:today',
            'max_member_count' => 'required|integer|min:1|max:100',
            'repo_link' => 'required|unique:projects,repo_link,' . $this->id,
            'review' => 'nullable|max:1000',
            'status' => 'required|exists:statuses,id',
            'type' => 'required|exists:types,id',
            'users' => 'nullable|array',
            'users.*.id' => 'required|exists:users,id',
            'users.*.role' => 'required|in:MAINTAINER,DEVELOPER',
            'stacks' => 'required|array|min:1',
            'stacks.*' => 'exists:stacks,id'
        ];
    }
}
