<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets through only students whose school ID was approved and whose
 * account is active. Everyone else gets HTTP 403 with their status,
 * so the Flutter app can open the "waiting for verification" screen.
 */
class EnsureVerifiedStudent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isStudent()) {
            return response()->json([
                'message' => 'Only student accounts can play HulaSalita games.',
            ], 403);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Your account has been deactivated. Please contact the administrator.',
                'status' => 'inactive',
            ], 403);
        }

        if ($user->status !== User::STATUS_APPROVED) {
            return response()->json([
                'message' => $user->status === User::STATUS_REJECTED
                    ? 'Your school ID was rejected. Please upload a clear photo of your school ID.'
                    : 'Your account is waiting for school ID verification.',
                'status' => $user->status,
            ], 403);
        }

        return $next($request);
    }
}
