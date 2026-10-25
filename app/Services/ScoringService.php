<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Puzzle;
use App\Models\User;

/**
 * Scoring rules (documentation, section 2.5):
 *  - Base points: easy 10, medium 20, hard 30.
 *  - Each failed try removes 1/5 of the base, but never below 2/5 of the base.
 *  - A student can earn at most 150 points per day (Asia/Manila date).
 */
class ScoringService
{
    public const DAILY_CAP = 150;

    /**
     * @param  int  $tries  total tries INCLUDING the correct one (1 = solved on the first try)
     */
    public function pointsFor(string $difficulty, int $tries): int
    {
        $base = Puzzle::BASE_POINTS[$difficulty] ?? 0;
        $failedTries = max(0, $tries - 1);

        $points = intdiv($base * max(0, 5 - $failedTries), 5);
        $minimum = intdiv($base * 2, 5);

        return max($points, $minimum);
    }

    /** Cut the points so the day's total never goes above the daily cap. */
    public function applyDailyCap(int $points, int $earnedToday): int
    {
        return max(0, min($points, self::DAILY_CAP - $earnedToday));
    }

    /** Points the student already earned on a date (Y-m-d, Asia/Manila). */
    public function pointsEarnedOn(User $user, string $date): int
    {
        return (int) Attempt::query()
            ->where('user_id', $user->id)
            ->whereDate('played_on', $date)
            ->sum('points');
    }
}
