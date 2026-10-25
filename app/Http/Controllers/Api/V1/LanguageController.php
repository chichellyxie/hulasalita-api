<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LanguageResource;
use App\Models\Language;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LanguageController extends Controller
{
    /** GET /api/v1/languages */
    public function index(): AnonymousResourceCollection
    {
        return LanguageResource::collection(Language::query()->orderBy('id')->get());
    }
}
