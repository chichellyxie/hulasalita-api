<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AdminCategoryController extends Controller
{
    /** GET /api/v1/admin/categories */
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::query()->orderBy('name')->get());
    }

    /** POST /api/v1/admin/categories */
    public function store(CategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    /** PUT /api/v1/admin/categories/{category} */
    public function update(CategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return new CategoryResource($category);
    }

    /** DELETE /api/v1/admin/categories/{category} — only if no puzzle uses it. */
    public function destroy(Category $category): Response|JsonResponse
    {
        if ($category->puzzles()->exists()) {
            return response()->json([
                'message' => 'Puzzles still use this category. Move or delete them first.',
            ], 422);
        }

        $category->delete();

        return response()->noContent();
    }
}
