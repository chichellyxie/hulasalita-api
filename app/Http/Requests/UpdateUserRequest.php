<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** PATCH /admin/users/{user} — deactivate or reactivate an account. */
class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
        ];
    }
}
