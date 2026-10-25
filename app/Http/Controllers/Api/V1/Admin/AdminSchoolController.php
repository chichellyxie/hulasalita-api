<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolRequest;
use App\Http\Resources\AdminSchoolResource;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AdminSchoolController extends Controller
{
    /** GET /api/v1/admin/schools — includes inactive schools. */
    public function index(): AnonymousResourceCollection
    {
        return AdminSchoolResource::collection(
            School::query()->withCount('users')->orderBy('name')->paginate(50)
        );
    }

    /** POST /api/v1/admin/schools */
    public function store(SchoolRequest $request): JsonResponse
    {
        $school = School::create($request->validated());

        return (new AdminSchoolResource($school))->response()->setStatusCode(201);
    }

    /** GET /api/v1/admin/schools/{school} */
    public function show(School $school): AdminSchoolResource
    {
        return new AdminSchoolResource($school->loadCount('users'));
    }

    /** PUT /api/v1/admin/schools/{school} — also used to deactivate (is_active=false). */
    public function update(SchoolRequest $request, School $school): AdminSchoolResource
    {
        $school->update($request->validated());

        return new AdminSchoolResource($school->loadCount('users'));
    }

    /** DELETE /api/v1/admin/schools/{school} — only if no user belongs to it. */
    public function destroy(School $school): Response|JsonResponse
    {
        if ($school->users()->exists()) {
            return response()->json([
                'message' => 'Students are registered under this school. Set it as inactive instead.',
            ], 422);
        }

        $school->delete();

        return response()->noContent();
    }
}
