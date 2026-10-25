<?php

namespace App\Http\Resources;

use App\Services\StreakService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** The logged-in user's own profile (never shown to other students). */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latest = $this->relationLoaded('latestVerification') ? $this->latestVerification : null;

        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'school' => $this->school ? new SchoolResource($this->school) : null,
            'current_streak' => app(StreakService::class)->effectiveStreak($this->resource),
            'last_played_on' => $this->last_played_on?->toDateString(),
            'verification' => $latest ? [
                'status' => $latest->status,
                'has_document' => $latest->hasDocument(),
                'submitted_at' => $latest->created_at?->toIso8601String(),
                'reviewed_at' => $latest->reviewed_at?->toIso8601String(),
            ] : null,
        ];
    }
}
