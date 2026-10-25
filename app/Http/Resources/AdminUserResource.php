<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'is_active' => $this->is_active,
            'school' => $this->school ? new SchoolResource($this->school) : null,
            'current_streak' => $this->current_streak,
            'last_played_on' => $this->last_played_on?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
