<?php

namespace App\Http\Resources;

use App\Models\Puzzle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Puzzle as a STUDENT sees it. The answer is NOT included unless this
 * student has already finished the puzzle (solved or out of tries).
 * Load the student's attempt with ->load(['attempts' => fn ($q) => $q->where('user_id', ...)]).
 */
class PuzzleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $attempt = $this->relationLoaded('attempts') ? $this->attempts->first() : null;
        $finished = $attempt && ($attempt->solved || $attempt->tries >= $this->max_tries);

        return [
            'id' => $this->id,
            'type' => $this->type,
            'difficulty' => $this->difficulty,
            'base_points' => $this->basePoints(),
            'prompt' => $this->prompt,
            'hint' => $this->hint,
            'max_tries' => $this->max_tries,
            // Tile count for the letter boards only — never the letters themselves.
            'answer_length' => $this->when(
                in_array($this->type, [Puzzle::TYPE_DAILY, Puzzle::TYPE_SCRAMBLE], true),
                fn () => $this->answerLength()
            ),
            'scheduled_for' => $this->when(
                $this->type === Puzzle::TYPE_DAILY,
                fn () => $this->scheduled_for?->toDateString()
            ),
            'language' => new LanguageResource($this->whenLoaded('language')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'my_attempt' => [
                'tries_used' => $attempt?->tries ?? 0,
                'tries_left' => max(0, $this->max_tries - ($attempt?->tries ?? 0)),
                'solved' => (bool) ($attempt?->solved ?? false),
                'finished' => $finished,
                'points' => $attempt?->points ?? 0,
            ],
            'answer' => $this->when($finished, fn () => $this->resource->answer),
        ];
    }
}
