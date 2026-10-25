<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GuessRequest;
use App\Http\Resources\GuessResultResource;
use App\Models\Puzzle;
use App\Services\GameService;

class GuessController extends Controller
{
    public function __construct(private readonly GameService $game) {}

    /**
     * POST /api/v1/puzzles/{puzzle}/guess   body: {"guess": "danum"}
     * Validation → GuessRequest, rules → GameService, JSON → GuessResultResource.
     */
    public function store(GuessRequest $request, Puzzle $puzzle): GuessResultResource
    {
        $result = $this->game->submitGuess(
            $request->user(),
            $puzzle,
            $request->validated('guess'),
        );

        return new GuessResultResource($result);
    }
}
