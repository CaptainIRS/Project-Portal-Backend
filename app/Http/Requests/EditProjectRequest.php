<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Auth\Access\AuthorizationException;
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
        return $this->user()->can('update', $this->project);
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function failedAuthorization()
    {
        throw new AuthorizationException('You are not allowed to edit this project!');
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

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (isset($this->users)) {
                if (count($this->users) + $this->project->users()->wherePivot('role', 'AUTHOR')->count()
                        > $this->max_member_count) {
                    $validator->errors()->add(
                        'max_member_count', 'Max member count is less than current user count'
                    );
                }
                $projectAuthors = $this->project->users()->wherePivot('role', 'AUTHOR')->get();
                foreach ($this->users as $user) {
                    if ($projectAuthors->contains($user['id'])) {
                        $validator->errors()->add('users', 'Author cannot take any other role');
                    }
                }
            }
        });
    }
}
