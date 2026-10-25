<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PuzzleIndexRequest;
use App\Http\Requests\StorePuzzleRequest;
use App\Http\Resources\AdminPuzzleResource;
use App\Models\Puzzle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AdminPuzzleController extends Controller
{
    /** GET /api/v1/admin/puzzles?type=&language=&difficulty=&category_id=&search= */
    public function index(PuzzleIndexRequest $request): AnonymousResourceCollection
    {
        $puzzles = Puzzle::query()
            ->when($request->input('type'), fn ($q, $type) => $q->where('type', $type))
            ->when($request->input('language'), fn ($q, $code) => $q->ofLanguage($code))
            ->when($request->input('difficulty'), fn ($q, $d) => $q->where('difficulty', $d))
            ->when($request->input('category_id'), fn ($q, $id) => $q->where('category_id', $id))
            ->when($request->input('search'), fn ($q, $s) => $q->where(
                fn ($w) => $w->where('prompt', 'like', "%{$s}%")->orWhere('answer', 'like', "%{$s}%")
            ))
            ->with(['language', 'category'])
            ->withCount('attempts')
            ->orderByDesc('id')
            ->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return AdminPuzzleResource::collection($puzzles);
    }

    /** POST /api/v1/admin/puzzles */
    public function store(StorePuzzleRequest $request): JsonResponse
    {
        $puzzle = Puzzle::create($request->validated());

        return (new AdminPuzzleResource($puzzle->load(['language', 'category'])))
            ->response()
            ->setStatusCode(201);
    }

    /** GET /api/v1/admin/puzzles/{puzzle} */
    public function show(Puzzle $puzzle): AdminPuzzleResource
    {
        return new AdminPuzzleResource($puzzle->load(['language', 'category'])->loadCount('attempts'));
    }

    /** PUT /api/v1/admin/puzzles/{puzzle} — send all fields. */
    public function update(StorePuzzleRequest $request, Puzzle $puzzle): AdminPuzzleResource
    {
        $data = $request->validated();
        $data['scheduled_for'] ??= null;
        $puzzle->update($data);

        return new AdminPuzzleResource($puzzle->load(['language', 'category'])->loadCount('attempts'));
    }

    /**
     * DELETE /api/v1/admin/puzzles/{puzzle}
     * A puzzle that students already played is kept, so their points and the
     * leaderboard stay correct. Edit it instead.
     */
    public function destroy(Puzzle $puzzle): Response|JsonResponse
    {
        if ($puzzle->attempts()->exists()) {
            return response()->json([
                'message' => 'This puzzle has already been played by students and cannot be deleted. Edit it instead.',
            ], 422);
        }

        $puzzle->delete();

        return response()->noContent();
    }
}
