<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Puzzle as the ADMIN sees it, including the answer. Admin routes only. */
class AdminPuzzleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'difficulty' => $this->difficulty,
            'base_points' => $this->basePoints(),
            'prompt' => $this->prompt,
            'hint' => $this->hint,
            'answer' => $this->resource->answer,
            'max_tries' => $this->max_tries,
            'scheduled_for' => $this->scheduled_for?->toDateString(),
            'language_id' => $this->language_id,
            'category_id' => $this->category_id,
            'language' => new LanguageResource($this->whenLoaded('language')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'attempts_count' => $this->whenCounted('attempts'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
