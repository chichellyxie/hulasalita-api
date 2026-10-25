<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SchoolResource;
use App\Models\School;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SchoolController extends Controller
{
    /** GET /api/v1/schools — public list for the registration dropdown. */
    public function index(): AnonymousResourceCollection
    {
        return SchoolResource::collection(
            School::query()->active()->orderBy('name')->get()
        );
    }
}
