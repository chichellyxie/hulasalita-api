<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LanguageRequest;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AdminLanguageController extends Controller
{
    /** GET /api/v1/admin/languages */
    public function index(): AnonymousResourceCollection
    {
        return LanguageResource::collection(Language::query()->orderBy('id')->get());
    }

    /** POST /api/v1/admin/languages */
    public function store(LanguageRequest $request): JsonResponse
    {
        $language = Language::create($request->validated());

        return (new LanguageResource($language))->response()->setStatusCode(201);
    }

    /** PUT /api/v1/admin/languages/{language} */
    public function update(LanguageRequest $request, Language $language): LanguageResource
    {
        $language->update($request->validated());

        return new LanguageResource($language);
    }

    /** DELETE /api/v1/admin/languages/{language} — only if no puzzle uses it. */
    public function destroy(Language $language): Response|JsonResponse
    {
        if ($language->puzzles()->exists()) {
            return response()->json([
                'message' => 'Puzzles still use this language. Move or delete them first.',
            ], 422);
        }

        $language->delete();

        return response()->noContent();
    }
}
