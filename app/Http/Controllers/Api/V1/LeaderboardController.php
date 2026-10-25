<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\Puzzle;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LeaderboardController extends Controller
{
    private const TOP = 50;

    /**
     * GET /api/v1/leaderboard
     * Points earned THIS WEEK (Monday–Sunday, Asia/Manila) by verified
     * students of the SAME school. Usernames only — no names, emails or photos.
     */
    public function index(Request $request): JsonResponse
    {
        $me = $request->user();
        $now = Carbon::now(Puzzle::TIMEZONE);
        $start = $now->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $end = $now->copy()->endOfWeek(Carbon::SUNDAY)->toDateString();

        $rows = Attempt::query()
            ->join('users', 'users.id', '=', 'attempts.user_id')
            ->where('users.school_id', $me->school_id)
            ->where('users.role', User::ROLE_STUDENT)
            ->where('users.status', User::STATUS_APPROVED)
            ->where('users.is_active', true)
            ->whereDate('attempts.played_on', '>=', $start)
            ->whereDate('attempts.played_on', '<=', $end)
            ->groupBy('users.id', 'users.username')
            ->selectRaw('users.id as user_id, users.username as username')
            ->selectRaw('SUM(attempts.points) as points')
            ->selectRaw('SUM(CASE WHEN attempts.solved = 1 THEN 1 ELSE 0 END) as solved')
            ->havingRaw('SUM(attempts.points) > 0')
            ->orderByDesc('points')
            ->orderBy('username')
            ->get();

        // Standard competition ranking: equal points share a rank (1, 2, 2, 4 ...).
        $ranked = [];
        $rank = 0;
        $previousPoints = null;
        foreach ($rows as $index => $row) {
            $points = (int) $row->points;
            if ($points !== $previousPoints) {
                $rank = $index + 1;
                $previousPoints = $points;
            }
            $ranked[] = [
                'rank' => $rank,
                'username' => $row->username,
                'points' => $points,
                'solved' => (int) $row->solved,
                'is_me' => (int) $row->user_id === $me->id,
            ];
        }

        $mine = collect($ranked)->firstWhere('is_me', true);

        return response()->json([
            'data' => array_slice($ranked, 0, self::TOP),
            'meta' => [
                'school' => $me->school?->name,
                'week_start' => $start,
                'week_end' => $end,
                'total_players' => count($ranked),
                'me' => [
                    'rank' => $mine['rank'] ?? null,
                    'points' => $mine['points'] ?? 0,
                ],
            ],
        ]);
    }
}
