<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps the array returned by GameService::submitGuess().
 * "answer" is null until the puzzle is finished.
 */
class GuessResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $r = $this->resource;

        return [
            'puzzle_id' => $r['puzzle_id'],
            'correct' => $r['correct'],
            'solved' => $r['solved'],
            'finished' => $r['finished'],
            'tries_used' => $r['tries_used'],
            'tries_left' => $r['tries_left'],
            'max_tries' => $r['max_tries'],
            'points_earned' => $r['points_earned'],
            'base_points' => $r['base_points'],
            'points_today' => $r['points_today'],
            'daily_cap' => $r['daily_cap'],
            'daily_cap_reached' => $r['daily_cap_reached'],
            'current_streak' => $r['current_streak'],
            'letters' => $r['letters'],
            'answer' => $r['answer'],
            'new_badges' => BadgeResource::collection($r['new_badges']),
        ];
    }
}
