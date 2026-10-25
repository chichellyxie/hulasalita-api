<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVerificationRequest;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VerificationController extends Controller
{
    /**
     * POST /api/v1/verification (multipart/form-data, field "id_image")
     * Upload or replace the school ID photo. Stored in PRIVATE storage
     * (storage/app/private/verifications/{user_id}/...), never public.
     */
    public function store(StoreVerificationRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->status === User::STATUS_APPROVED) {
            return response()->json(['message' => 'Your account is already verified.'], 403);
        }

        $path = $request->file('id_image')->store("verifications/{$user->id}", 'local');

        $verification = DB::transaction(function () use ($user, $path) {
            $verification = $user->verifications()->pending()->latest('id')->first();

            if ($verification) {
                // Replace the old photo of the same pending request.
                if ($verification->id_image_path) {
                    Storage::disk('local')->delete($verification->id_image_path);
                }
                $verification->update(['id_image_path' => $path]);
            } else {
                $verification = $user->verifications()->create([
                    'id_image_path' => $path,
                    'status' => Verification::STATUS_PENDING,
                ]);
            }

            // A rejected student who uploads again goes back to pending.
            $user->forceFill(['status' => User::STATUS_PENDING])->save();

            return $verification;
        });

        return response()->json([
            'message' => 'School ID uploaded. Please wait for the administrator to review it.',
            'data' => [
                'verification_id' => $verification->id,
                'status' => $verification->status,
                'submitted_at' => $verification->updated_at?->toIso8601String(),
            ],
        ], 201);
    }
}
