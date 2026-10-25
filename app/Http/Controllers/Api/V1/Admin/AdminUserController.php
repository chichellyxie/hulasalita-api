<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\AdminUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminUserController extends Controller
{
    /** GET /api/v1/admin/users?role=student&status=pending&school_id=1&search=juan */
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'role' => ['nullable', 'in:student,admin'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'school_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $users = User::query()
            ->when($request->input('role'), fn ($q, $role) => $q->where('role', $role))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->input('school_id'), fn ($q, $id) => $q->where('school_id', $id))
            ->when($request->input('search'), fn ($q, $s) => $q->where(
                fn ($w) => $w->where('username', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")
            ))
            ->with('school')
            ->orderBy('username')
            ->paginate(20)
            ->withQueryString();

        return AdminUserResource::collection($users);
    }

    /** PATCH /api/v1/admin/users/{user}   body: {"is_active": false} */
    public function update(UpdateUserRequest $request, User $user): AdminUserResource|JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 422);
        }

        $user->forceFill(['is_active' => $request->boolean('is_active')])->save();

        // A deactivated user is logged out of every device at once.
        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        return new AdminUserResource($user->load('school'));
    }
}
