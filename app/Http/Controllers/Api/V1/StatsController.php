<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BadgeResource;
use App\Models\Attempt;
use App\Models\Badge;
use App\Models\Puzzle;
use App\Services\BadgeService;
use App\Services\ScoringService;
use App\Services\StreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StatsController extends Controller
{
    public function __construct(
        private readonly ScoringService $scoring,
        private readonly StreakService $streaks,
        private readonly BadgeService $badges,
    ) {}

    /** GET /api/v1/me/stats — points, streak and badges of the logged-in student. */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $today = Carbon::now(Puzzle::TIMEZONE)->toDateString();
        $stats = $this->badges->statsFor($user);
        $pointsToday = $this->scoring->pointsEarnedOn($user, $today);

        $earned = $user->badges()->get()->keyBy('id');
        $allBadges = Badge::query()->orderBy('id')->get()
            ->map(fn (Badge $badge) => $earned->get($badge->id) ?? $badge);

        return response()->json([
            'data' => [
                'username' => $user->username,
                'school' => $user->school?->name,
                'total_points' => $stats['points'],
                'points_today' => $pointsToday,
                'points_left_today' => max(0, ScoringService::DAILY_CAP - $pointsToday),
                'daily_cap' => ScoringService::DAILY_CAP,
                'points_this_week' => (int) Attempt::query()->where('user_id', $user->id)->thisWeek()->sum('points'),
                'current_streak' => $this->streaks->effectiveStreak($user),
                'last_played_on' => $user->last_played_on?->toDateString(),
                'puzzles_solved' => $stats['solved'],
                'solved_by_type' => $stats['by_type'],
                'languages_played' => $stats['languages'],
                'badges' => BadgeResource::collection($allBadges),
            ],
        ]);
    }
}
