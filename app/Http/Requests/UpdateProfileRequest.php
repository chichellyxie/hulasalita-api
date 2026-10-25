<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'sometimes', 'required', 'string', 'alpha_dash', 'min:3', 'max:30',
                Rule::unique('users', 'username')->ignore($this->user()->id),
            ],
            'current_password' => ['required_with:password', 'current_password:sanctum'],
            'password' => ['sometimes', 'required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }
}
