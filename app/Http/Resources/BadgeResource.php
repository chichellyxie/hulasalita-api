<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * earned_at comes from the user_badges pivot (when loaded through
 * $user->badges) or from the attribute BadgeService sets on new badges.
 */
class BadgeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $earnedAt = $this->pivot?->earned_at ?? $this->getAttribute('earned_at');

        return [
            'code' => $this->code,
            'name' => $this->name,
            'rule' => $this->rule,
            'earned' => $earnedAt !== null,
            'earned_at' => $earnedAt ? Carbon::parse($earnedAt)->toIso8601String() : null,
        ];
    }
}
