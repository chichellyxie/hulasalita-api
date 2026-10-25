<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** Admin view of a verification. The file path is never exposed. */
class VerificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'has_document' => $this->hasDocument(),
            'document_url' => $this->hasDocument()
                ? url("/api/v1/admin/verifications/{$this->id}/document")
                : null,
            'submitted_at' => $this->created_at?->toIso8601String(),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'reviewed_by' => $this->whenLoaded('reviewer', fn () => $this->reviewer?->username),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'email' => $this->user->email,
                'status' => $this->user->status,
                'school' => $this->user->school?->name,
            ]),
        ];
    }
}
