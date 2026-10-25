<?php

namespace App\Http\Requests;

use App\Models\Puzzle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Used for POST and PUT /admin/puzzles — send every field (full replace). */
class StorePuzzleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isDaily = $this->input('type') === Puzzle::TYPE_DAILY;

        return [
            'language_id' => ['required', 'integer', 'exists:languages,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'type' => ['required', Rule::in(Puzzle::TYPES)],
            'difficulty' => ['required', Rule::in(array_keys(Puzzle::BASE_POINTS))],
            'prompt' => ['required', 'string', 'max:1000'],
            'hint' => ['nullable', 'string', 'max:255'],
            'answer' => array_merge(
                ['required', 'string', 'max:100'],
                $isDaily ? ['regex:/^\pL+$/u'] : []   // daily words: letters only (no spaces)
            ),
            'max_tries' => ['required', 'integer', 'min:1', 'max:10'],
            'scheduled_for' => [
                'nullable',
                'date_format:Y-m-d',
                'required_if:type,'.Puzzle::TYPE_DAILY,
                'prohibited_unless:type,'.Puzzle::TYPE_DAILY,
                Rule::unique('puzzles', 'scheduled_for')->ignore($this->route('puzzle')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'answer.regex' => 'A daily word may contain letters only.',
            'scheduled_for.required_if' => 'A daily puzzle needs a date.',
            'scheduled_for.prohibited_unless' => 'Only daily puzzles can have a date.',
            'scheduled_for.unique' => 'Another daily puzzle is already scheduled on this date.',
        ];
    }
}
