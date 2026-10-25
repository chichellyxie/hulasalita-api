<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'alpha', 'max:5', Rule::unique('languages', 'code')->ignore($this->route('language'))],
            'name' => ['required', 'string', 'max:50'],
        ];
    }
}
