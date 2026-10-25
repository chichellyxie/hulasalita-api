<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DecideVerificationRequest;
use App\Http\Resources\VerificationResource;
use App\Models\Verification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminVerificationController extends Controller
{
    /** GET /api/v1/admin/verifications — pending requests, oldest first. */
    public function index(Request $request): AnonymousResourceCollection
    {
        $verifications = Verification::query()
            ->pending()
            ->whereNotNull('id_image_path')
            ->with(['user.school'])
            ->oldest()
            ->paginate(20);

        return VerificationResource::collection($verifications);
    }

    /** GET /api/v1/admin/verifications/{verification}/document — streams the private photo. */
    public function document(Verification $verification): StreamedResponse|JsonResponse
    {
        $disk = Storage::disk('local');

        if (! $verification->hasDocument() || ! $disk->exists($verification->id_image_path)) {
            return response()->json(['message' => 'The ID photo is no longer available.'], 404);
        }

        return $disk->response($verification->id_image_path, null, [
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * PATCH /api/v1/admin/verifications/{verification}   body: {"decision": "approved"|"rejected"}
     * Saves the decision on the verification and the user, then DELETES the ID file.
     */
    public function decide(DecideVerificationRequest $request, Verification $verification): VerificationResource|JsonResponse
    {
        if (! $verification->isPending()) {
            return response()->json(['message' => 'This verification has already been decided.'], 422);
        }

        $decision = $request->validated('decision');
        $filePath = $verification->id_image_path;

        DB::transaction(function () use ($verification, $decision, $request) {
            $verification->forceFill([
                'status' => $decision,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'id_image_path' => null,
            ])->save();

            $verification->user->forceFill(['status' => $decision])->save();
        });

        // Data privacy: the uploaded ID is removed as soon as a decision is made.
        if ($filePath) {
            Storage::disk('local')->delete($filePath);
        }

        return new VerificationResource($verification->fresh(['user.school', 'reviewer']));
    }
}
