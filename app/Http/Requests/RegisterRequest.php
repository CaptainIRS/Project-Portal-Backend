<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'email' => 'email|required|unique:users,email',
            'password' => 'required|confirmed',
            'roll_number' => 'required|integer|digits:9|unique:users,roll_number',
            'github_handle' => 'required|max:255'
        ];
    }
}