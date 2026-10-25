<?php

namespace App\Http\Requests;

use App\Models\Puzzle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Filters for GET /puzzles (students) and GET /admin/puzzles. */
class PuzzleIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::in(Puzzle::TYPES)],
            'language' => ['nullable', 'string', 'exists:languages,code'],
            'difficulty' => ['nullable', Rule::in(array_keys(Puzzle::BASE_POINTS))],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
