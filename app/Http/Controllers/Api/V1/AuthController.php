<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /** POST /api/v1/auth/register */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = new User($request->only(['username', 'email', 'password', 'school_id']));
        $user->forceFill([
            'role' => User::ROLE_STUDENT,
            'status' => User::STATUS_PENDING,
            'is_active' => true,
            'current_streak' => 0,
        ])->save();

        $token = $user->createToken($request->input('device_name', 'hulasalita-app'))->plainTextToken;

        return response()->json([
            'message' => 'Account created. Please upload a photo of your school ID for verification.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->load(['school', 'latestVerification'])),
        ], 201);
    }

    /** POST /api/v1/auth/login — "login" can be the username or the email. */
    public function login(LoginRequest $request): JsonResponse
    {
        $login = trim($request->input('login'));
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::query()->where($field, $login)->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The username/email or password is incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'This account has been deactivated. Please contact the administrator.',
                'status' => 'inactive',
            ], 403);
        }

        $token = $user->createToken($request->input('device_name', 'hulasalita-app'))->plainTextToken;

        return response()->json([
            'message' => 'Logged in successfully.',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user->load(['school', 'latestVerification'])),
        ]);
    }

    /** POST /api/v1/auth/logout — revokes only the token used for this request. */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /** GET /api/v1/me */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user()->load(['school', 'latestVerification']));
    }

    /** PUT /api/v1/me — change username and/or password. */
    public function updateMe(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();

        if ($request->filled('username')) {
            $user->username = $request->input('username');
        }

        if ($request->filled('password')) {
            $user->password = $request->input('password');   // hashed by the model cast
        }

        $user->save();

        return new UserResource($user->load(['school', 'latestVerification']));
    }
}
