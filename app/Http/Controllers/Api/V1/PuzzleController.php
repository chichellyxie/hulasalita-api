<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PuzzleIndexRequest;
use App\Http\Resources\PuzzleResource;
use App\Models\Puzzle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class PuzzleController extends Controller
{
    /** GET /api/v1/puzzles/daily — today's puzzle (Asia/Manila), same for everyone. */
    public function daily(Request $request): PuzzleResource|JsonResponse
    {
        $puzzle = Puzzle::query()->dailyForToday()->with(['language', 'category'])->first();

        if (! $puzzle) {
            return response()->json([
                'message' => 'There is no daily puzzle for today yet. Please check again later.',
            ], 404);
        }

        return new PuzzleResource($this->withMyAttempt($puzzle, $request));
    }

    /**
     * GET /api/v1/puzzles?type=bugtong&language=pag&difficulty=easy&category_id=1&per_page=20
     * Bugtong, Word Scramble and Translate It. Daily puzzles are only served by /puzzles/daily.
     */
    public function index(PuzzleIndexRequest $request): AnonymousResourceCollection
    {
        $userId = $request->user()->id;

        $puzzles = Puzzle::query()
            ->where('type', '!=', Puzzle::TYPE_DAILY)
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->input('language'), fn ($q, $code) => $q->ofLanguage($code))
            ->when($request->input('difficulty'), fn ($q, $d) => $q->where('difficulty', $d))
            ->when($request->input('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->with([
                'language',
                'category',
                'attempts' => fn ($q) => $q->where('user_id', $userId),
            ])
            ->orderBy('id')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return PuzzleResource::collection($puzzles);
    }

    /** GET /api/v1/puzzles/{puzzle} — never includes the answer before the puzzle is finished. */
    public function show(Request $request, Puzzle $puzzle): PuzzleResource|JsonResponse
    {
        // Past and future daily puzzles cannot be opened.
        if ($puzzle->type === Puzzle::TYPE_DAILY
            && $puzzle->scheduled_for?->toDateString() !== Carbon::now(Puzzle::TIMEZONE)->toDateString()) {
            return response()->json(['message' => 'Resource not found.'], 404);
        }

        return new PuzzleResource($this->withMyAttempt($puzzle->load(['language', 'category']), $request));
    }

    private function withMyAttempt(Puzzle $puzzle, Request $request): Puzzle
    {
        return $puzzle->load([
            'attempts' => fn ($q) => $q->where('user_id', $request->user()->id),
        ]);
    }
}
