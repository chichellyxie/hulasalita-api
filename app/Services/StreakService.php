<?php

namespace App\Services;

use App\Models\Puzzle;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Streak rules (documentation, section 2.5):
 *  - The streak grows by 1 when the student solves at least one puzzle on the
 *    day right after the last playing day.
 *  - Solving again on the same day changes nothing.
 *  - Any gap of one day or more restarts the streak at 1.
 * All dates use the Asia/Manila time zone.
 */
class StreakService
{
    /** Call this after a puzzle is SOLVED. */
    public function recordPlay(User $user, ?Carbon $now = null): void
    {
        $today = ($now ?? Carbon::now(Puzzle::TIMEZONE))->copy()->setTimezone(Puzzle::TIMEZONE);
        $todayDate = $today->toDateString();
        $yesterdayDate = $today->copy()->subDay()->toDateString();
        $lastDate = $user->last_played_on?->toDateString();

        if ($lastDate === $todayDate) {
            return; // already counted today
        }

        $user->current_streak = $lastDate === $yesterdayDate
            ? $user->current_streak + 1
            : 1;
        $user->last_played_on = $todayDate;
        $user->save();
    }

    /**
     * The streak to SHOW. If the student missed yesterday, the stored
     * streak is already broken, so 0 is shown until the next solve.
     */
    public function effectiveStreak(User $user): int
    {
        $lastDate = $user->last_played_on?->toDateString();

        if ($lastDate === null) {
            return 0;
        }

        $yesterdayDate = Carbon::now(Puzzle::TIMEZONE)->subDay()->toDateString();

        return $lastDate >= $yesterdayDate ? (int) $user->current_streak : 0;
    }
}
